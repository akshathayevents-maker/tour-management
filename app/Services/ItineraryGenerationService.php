<?php

namespace App\Services;

use App\Models\ItineraryTemplate;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Builds a trip's itinerary from an ordered list of destination segments,
 * each optionally backed by a reusable ItineraryTemplate. Templates are
 * copied — never referenced live — so later edits to either side never
 * cross-contaminate the other (see ItineraryTemplate docblock).
 *
 * Trip::itinerary() is a hasOne (itineraries.trip_id is unique — see
 * migration), so "generate" always targets the trip's single itinerary:
 * create it if absent, otherwise replace its days. Replacement is only
 * ever performed when the caller has already confirmed it (enforced by
 * GenerateItineraryRequest, not re-checked here — this service trusts its
 * caller the same way the rest of the app's services do).
 */
class ItineraryGenerationService
{
    /**
     * @param  array<int, array{destination: string, days: int, notes?: string|null, itinerary_template_id?: int|null}>  $segments
     */
    public function generate(Trip $trip, array $segments): void
    {
        DB::transaction(function () use ($trip, $segments) {
            // Query fresh rather than the `itinerary` magic property: if
            // this Trip instance already cached a null relation (e.g. an
            // earlier "does it have one?" check before one existed), the
            // cached null would never be invalidated by create() below,
            // and a second generate() call on the same instance would try
            // to create a second itinerary and hit the trip_id unique
            // constraint.
            $itinerary = $trip->itinerary()->first() ?? $trip->itinerary()->create([
                'company_id' => $trip->company_id,
            ]);

            // Replace, don't accumulate — regenerating must not duplicate
            // or leave orphaned days from a previous build.
            $itinerary->days()->delete();

            $dayNumber = 1;

            foreach ($segments as $segment) {
                $template = isset($segment['itinerary_template_id'])
                    ? ItineraryTemplate::with('days.items')->find($segment['itinerary_template_id'])
                    : null;

                $templateDays = $template?->days ?? collect();
                $requestedDays = (int) $segment['days'];

                for ($i = 0; $i < $requestedDays; $i++) {
                    // More requested days than the template has: the extra
                    // days are created empty (still editable) rather than
                    // inventing or repeating content. Fewer requested days
                    // than the template has: the template's trailing days
                    // are simply not used — never silently truncating
                    // *within* a day, only dropping whole unused days.
                    $templateDay = $templateDays->get($i);

                    $day = $itinerary->days()->create([
                        'company_id' => $trip->company_id,
                        'day_number' => $dayNumber,
                        'destination' => $segment['destination'],
                        'notes' => $templateDay?->notes ?? ($segment['notes'] ?? null),
                        'source_itinerary_template_day_id' => $templateDay?->id,
                    ]);

                    foreach ($templateDay?->items ?? [] as $templateItem) {
                        $day->items()->create([
                            'company_id' => $trip->company_id,
                            'title' => $templateItem->title,
                            'time' => $templateItem->time,
                            'notes' => $templateItem->notes,
                            'sort_order' => $templateItem->sort_order,
                        ]);
                    }

                    $dayNumber++;
                }
            }
        });
    }
}
