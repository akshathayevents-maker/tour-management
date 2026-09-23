<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItineraryDay\StoreItineraryDayRequest;
use App\Http\Requests\ItineraryDay\UpdateItineraryDayRequest;
use App\Models\Itinerary;
use App\Models\ItineraryDay;
use Illuminate\Http\RedirectResponse;

class ItineraryDayController extends Controller
{
    public function store(StoreItineraryDayRequest $request, Itinerary $itinerary): RedirectResponse
    {
        $itinerary->days()->create($request->validated());

        return redirect()
            ->route('trips.show', $itinerary->trip)
            ->with('status', 'Day added.');
    }

    public function update(UpdateItineraryDayRequest $request, ItineraryDay $itineraryDay): RedirectResponse
    {
        $itineraryDay->update($request->validated());

        return redirect()
            ->route('trips.show', $itineraryDay->itinerary->trip)
            ->with('status', 'Day updated.');
    }

    public function destroy(ItineraryDay $itineraryDay): RedirectResponse
    {
        $this->authorize('update', $itineraryDay->itinerary->trip);

        $trip = $itineraryDay->itinerary->trip;
        $itineraryDay->delete();

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', 'Day removed.');
    }
}
