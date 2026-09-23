<?php

namespace App\Http\Controllers;

use App\Http\Requests\Itinerary\StoreItineraryRequest;
use App\Http\Requests\Itinerary\UpdateItineraryRequest;
use App\Models\Itinerary;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;

class ItineraryController extends Controller
{
    public function store(StoreItineraryRequest $request, Trip $trip): RedirectResponse
    {
        $trip->itinerary()->create($request->validated());

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', 'Itinerary created.');
    }

    public function update(UpdateItineraryRequest $request, Itinerary $itinerary): RedirectResponse
    {
        $itinerary->update($request->validated());

        return redirect()
            ->route('trips.show', $itinerary->trip)
            ->with('status', 'Itinerary updated.');
    }
}
