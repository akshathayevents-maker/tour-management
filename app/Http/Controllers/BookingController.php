<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\QuotationVersionStatus;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\QuotationVersion;
use App\Models\Trip;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::query()
            ->with('customer', 'trip')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('bookings.index', [
            'bookings' => $bookings,
            'statuses' => BookingStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Booking::class);

        $customer = $request->filled('customer_id')
            ? Customer::findOrFail($request->integer('customer_id'))
            : null;

        $trip = $request->filled('trip_id')
            ? Trip::findOrFail($request->integer('trip_id'))
            : null;

        return view('bookings.create', compact('customer', 'trip'));
    }

    public function store(StoreBookingRequest $request, BookingService $bookings): RedirectResponse
    {
        $trip = Trip::findOrFail($request->integer('trip_id'));

        $booking = $bookings->createDirect($trip);

        activity()->performedOn($booking)->causedBy($request->user())->log('Booking created directly.');

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Booking created.');
    }

    public function storeFromQuotation(QuotationVersion $quotationVersion, BookingService $bookings): RedirectResponse
    {
        $this->authorize('view', $quotationVersion->quotation);

        if ($quotationVersion->status !== QuotationVersionStatus::Accepted) {
            abort(422, 'Only an accepted quotation version can become a booking.');
        }

        $booking = $bookings->createFromQuotationVersion($quotationVersion);

        activity()->performedOn($booking)->causedBy(request()->user())
            ->log("Booking created from Quotation V{$quotationVersion->version_number}.");

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Booking created from the accepted quotation.');
    }

    public function show(Booking $booking): View
    {
        $this->authorize('view', $booking);

        $booking->load([
            'customer', 'trip',
            'acceptedQuotationVersion.lineItems',
            'items.supplier',
            'checklistItems.doneBy',
        ]);

        $activities = Activity::forSubject($booking)->latest()->limit(20)->get();

        return view('bookings.show', compact('booking', 'activities'));
    }

    public function cancel(CancelBookingRequest $request, Booking $booking): RedirectResponse
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            abort(422, 'Only a confirmed booking can be cancelled.');
        }

        $booking->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->string('cancellation_reason')->value() ?: null,
        ]);

        activity()->performedOn($booking)->causedBy($request->user())->log('Booking cancelled.');

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Booking cancelled.');
    }

    public function complete(Booking $booking): RedirectResponse
    {
        $this->authorize('update', $booking);

        if ($booking->status !== BookingStatus::Confirmed) {
            abort(422, 'Only a confirmed booking can be marked completed.');
        }

        $booking->update([
            'status' => BookingStatus::Completed,
            'completed_at' => now(),
        ]);

        activity()->performedOn($booking)->causedBy(request()->user())->log('Booking marked completed.');

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Booking marked completed.');
    }
}
