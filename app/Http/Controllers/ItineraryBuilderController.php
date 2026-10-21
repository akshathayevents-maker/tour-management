<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItineraryBuilder\GenerateItineraryRequest;
use App\Models\ItineraryTemplate;
use App\Models\Trip;
use App\Services\ItineraryGenerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ItineraryBuilderController extends Controller
{
    public function create(Trip $trip): View
    {
        $this->authorize('update', $trip);

        $trip->load('itinerary.days.items');

        $templates = ItineraryTemplate::query()
            ->where('is_active', true)
            ->withCount('days')
            ->orderBy('destination')
            ->orderBy('name')
            ->get();

        return view('itineraries.builder', compact('trip', 'templates'));
    }

    public function store(GenerateItineraryRequest $request, Trip $trip, ItineraryGenerationService $generator): RedirectResponse
    {
        $generator->generate($trip, $request->validated('segments'));

        activity()->performedOn($trip)->causedBy($request->user())
            ->log('Itinerary generated from templates.');

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', 'Itinerary generated. Review and customize the days below.');
    }
}
