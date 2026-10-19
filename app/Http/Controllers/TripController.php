<?php

namespace App\Http\Controllers;

use App\Enums\TripStatus;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class TripController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Trip::class);

        $trips = Trip::query()
            ->with('customer')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('trips.index', [
            'trips' => $trips,
            'statuses' => TripStatus::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Trip::class);

        $customer = null;
        $enquiry = null;

        if ($request->filled('enquiry_id')) {
            $enquiry = Enquiry::with('customer')->findOrFail($request->integer('enquiry_id'));
            $customer = $enquiry->customer;
        } elseif ($request->filled('customer_id')) {
            $customer = Customer::findOrFail($request->integer('customer_id'));
        }

        return view('trips.create', compact('customer', 'enquiry'));
    }

    public function store(StoreTripRequest $request): RedirectResponse
    {
        $trip = Trip::create($request->validated());

        activity()->performedOn($trip)->causedBy(request()->user())->log("Trip to \"{$trip->destination}\" created.");

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', "Trip to \"{$trip->destination}\" created.");
    }

    public function show(Trip $trip): View
    {
        $this->authorize('view', $trip);

        $trip->load([
            'customer', 'enquiry',
            'itinerary.days.items',
            'quotation.versions.lineItems',
            'bookings.items', 'bookings.checklistItems',
        ]);

        $followUps = $trip->followUps()->pending()->orderBy('due_at')->get();
        $activities = Activity::forSubject($trip)->with('causer')->latest()->limit(20)->get();

        return view('trips.show', compact('trip', 'followUps', 'activities'));
    }

    public function edit(Trip $trip): View
    {
        $this->authorize('update', $trip);

        return view('trips.edit', compact('trip'));
    }

    public function update(UpdateTripRequest $request, Trip $trip): RedirectResponse
    {
        $originalStatus = $trip->status;

        $trip->update($request->validated());

        if ($trip->status !== $originalStatus) {
            activity()->performedOn($trip)->causedBy(request()->user())
                ->log("Trip status changed from {$originalStatus->label()} to {$trip->status->label()}.");
        }

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', 'Trip updated.');
    }
}
