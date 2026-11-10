<?php

namespace App\Http\Controllers;

use App\Enums\TripStatus;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        // Same arithmetic as Booking::amountRemaining() (totalSellPrice -
        // totalPaid, voided payments excluded) expressed as a correlated
        // subquery so "outstanding balance" can be filtered/summed in SQL
        // without loading every booking's items/payments into PHP.
        $outstandingPerBookingSql = '(
            (select coalesce(sum(bi.sell_price), 0) from booking_items bi where bi.booking_id = bookings.id)
            - (select coalesce(sum(cp.amount), 0) from customer_payments cp where cp.booking_id = bookings.id and cp.voided_at is null)
        )';
        $outstandingBalanceSql = $outstandingPerBookingSql.' > 0';

        $query = Customer::query()
            ->withCount('bookings')
            ->with([
                'enquiries' => fn ($q) => $q->latest()->limit(1),
                'trips' => fn ($q) => $q->latest('start_date'),
                // Bounded to this page's 20 customers (not every booking in
                // the table) — items/payments loaded once so the card's
                // financial summary can reuse Booking::totalSellPrice() /
                // totalPaid() / amountRemaining() instead of re-deriving
                // the arithmetic in the view.
                'bookings.items', 'bookings.payments',
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q');
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        match ($request->string('filter')->value()) {
            'with_bookings' => $query->whereHas('bookings'),
            'upcoming_trips' => $query->whereHas('trips', fn ($q) => $q
                ->where('status', '!=', TripStatus::Cancelled)
                ->where('start_date', '>=', now()->toDateString())),
            'with_balance' => $query->whereHas('bookings', fn ($q) => $q->whereRaw($outstandingBalanceSql)),
            'recent' => $query->where('created_at', '>=', now()->startOfMonth()),
            default => null,
        };

        $customers = $query->latest()->paginate(20)->withQueryString();

        // Per-card financial/travel summary, computed once here from the
        // already-eager-loaded collections (same reasoning as the
        // Customer 360 profile) so the view never re-derives this math.
        $customers->getCollection()->transform(function (Customer $customer) {
            $customer->totalBookingValue = $customer->bookings->sum(fn ($b) => $b->totalSellPrice());
            $customer->totalCollected = $customer->bookings->sum(fn ($b) => $b->totalPaid());
            $customer->outstandingBalance = $customer->bookings->sum(fn ($b) => $b->amountRemaining());
            $customer->upcomingTrip = $customer->trips->first(fn ($t) => $t->status !== TripStatus::Cancelled
                && $t->start_date !== null
                && $t->start_date->isFuture());

            return $customer;
        });

        $total = Customer::count();
        $withBookings = Customer::whereHas('bookings')->count();

        $stats = [
            'total' => $total,
            'withBookings' => $withBookings,
            'withBookingsPct' => $total > 0 ? (int) round($withBookings / $total * 100) : 0,
            'upcomingTrips' => Customer::whereHas('trips', fn ($q) => $q
                ->where('status', '!=', TripStatus::Cancelled)
                ->where('start_date', '>=', now()->toDateString()))->count(),
            'nextTravelDate' => Trip::query()
                ->where('status', '!=', TripStatus::Cancelled)
                ->where('start_date', '>=', now()->toDateString())
                ->orderBy('start_date')
                ->value('start_date'),
            'addedThisMonth' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            'withBalance' => Customer::whereHas('bookings', fn ($q) => $q->whereRaw($outstandingBalanceSql))->count(),
            'outstandingTotal' => (float) (Booking::query()
                ->selectRaw("coalesce(sum({$outstandingPerBookingSql}), 0) as total")
                ->value('total') ?? 0),
        ];

        return view('customers.index', compact('customers', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        activity()->performedOn($customer)->causedBy(request()->user())
            ->log("Customer \"{$customer->name}\" created.");

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Customer \"{$customer->name}\" created.");
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->load([
            'enquiries' => fn ($query) => $query->latest(),
            'leads' => fn ($query) => $query->latest(),
        ]);

        $trips = $customer->trips()
            ->with(['enquiry', 'quotation.versions.lineItems', 'itinerary'])
            ->latest('start_date')
            ->get();

        // The inverse of Quotation::trip() isn't populated by eager-loading
        // the forward quotation() relation, so set it manually to avoid an
        // N+1 when the view reads $quotation->trip->destination.
        $trips->each(fn ($trip) => $trip->quotation?->setRelation('trip', $trip));

        // Loaded once here (items + payments) so Booking::totalSellPrice(),
        // totalPaid() and amountRemaining() — which sum already-loaded
        // collections rather than querying — can be reused everywhere
        // below without N+1s or re-deriving the arithmetic.
        $bookings = $customer->bookings()
            ->with(['items', 'payments', 'trip'])
            ->latest()
            ->get();

        $totalBookingValue = $bookings->sum(fn ($booking) => $booking->totalSellPrice());
        $totalCollected = $bookings->sum(fn ($booking) => $booking->totalPaid());
        $outstandingBalance = $bookings->sum(fn ($booking) => $booking->amountRemaining());

        $upcomingTrip = $trips->first(fn ($trip) => $trip->status !== TripStatus::Cancelled
            && $trip->start_date !== null
            && $trip->start_date->isFuture());

        $quotations = $trips->pluck('quotation')->filter()->values();

        // Keyed by enquiry_id so the Enquiries section can show "converted
        // to trip" without an extra query per row — every trip already
        // carries its originating enquiry_id (nullable, set when a trip
        // is created from an enquiry rather than ad-hoc).
        $tripByEnquiryId = $trips->filter(fn ($trip) => $trip->enquiry_id !== null)
            ->keyBy('enquiry_id');

        $customerPayments = $bookings->flatMap(fn ($booking) => $booking->payments)
            ->sortByDesc('paid_at')
            ->values();

        $followUps = $customer->followUps()->orderByDesc('due_at')->get();
        $activities = Activity::forSubject($customer)->with('causer')->latest()->limit(20)->get();

        return view('customers.show', compact(
            'customer', 'trips', 'bookings', 'quotations', 'tripByEnquiryId',
            'totalBookingValue', 'totalCollected', 'outstandingBalance', 'upcomingTrip',
            'customerPayments', 'followUps', 'activities',
        ));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'Customer updated.');
    }
}
