<?php

namespace App\Http\Controllers;

use App\Http\Requests\Itinerary\StoreItineraryRequest;
use App\Http\Requests\Itinerary\UpdateItineraryRequest;
use App\Models\Itinerary;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ItineraryController extends Controller
{
    public function store(StoreItineraryRequest $request, Trip $trip): RedirectResponse
    {
        $trip->itinerary()->create($request->validated());

        activity()->performedOn($trip)->causedBy(request()->user())->log('Itinerary created.');

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', 'Itinerary created.');
    }

    /**
     * Print-friendly customer-facing itinerary — same authorization as
     * Trip::show(), chrome-free view. A trip without an itinerary (or an
     * itinerary without days) is not an error — the view just renders
     * an empty state, matching how trips/show.blade.php already treats
     * "no itinerary yet" as a normal state, not a 404/422.
     */
    public function print(Trip $trip): View
    {
        $this->authorize('view', $trip);

        $trip->load(['customer', 'company', 'itinerary.days.items']);

        return view('itineraries.print', compact('trip'));
    }

    public function update(UpdateItineraryRequest $request, Itinerary $itinerary): RedirectResponse
    {
        $originalNotes = $itinerary->notes;

        $itinerary->update($request->validated());

        if ($itinerary->notes !== $originalNotes) {
            activity()->performedOn($itinerary->trip)->causedBy(request()->user())->log('Itinerary notes updated.');
        }

        return redirect()
            ->route('trips.show', $itinerary->trip)
            ->with('status', 'Itinerary updated.');
    }
}
