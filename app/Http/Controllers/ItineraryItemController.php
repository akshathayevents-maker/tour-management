<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItineraryItem\StoreItineraryItemRequest;
use App\Http\Requests\ItineraryItem\UpdateItineraryItemRequest;
use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItineraryItemController extends Controller
{
    public function store(StoreItineraryItemRequest $request, ItineraryDay $itineraryDay): RedirectResponse
    {
        $nextOrder = ($itineraryDay->items()->max('sort_order') ?? -1) + 1;

        $itineraryDay->items()->create([
            ...$request->validated(),
            'sort_order' => $nextOrder,
        ]);

        return redirect()
            ->route('trips.show', $itineraryDay->itinerary->trip)
            ->with('status', 'Item added.');
    }

    public function update(UpdateItineraryItemRequest $request, ItineraryItem $itineraryItem): RedirectResponse
    {
        $itineraryItem->update($request->validated());

        return redirect()
            ->route('trips.show', $itineraryItem->itineraryDay->itinerary->trip)
            ->with('status', 'Item updated.');
    }

    public function destroy(ItineraryItem $itineraryItem): RedirectResponse
    {
        $trip = $itineraryItem->itineraryDay->itinerary->trip;
        $this->authorize('update', $trip);

        $itineraryItem->delete();

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', 'Item removed.');
    }

    public function move(Request $request, ItineraryItem $itineraryItem): RedirectResponse
    {
        $trip = $itineraryItem->itineraryDay->itinerary->trip;
        $this->authorize('update', $trip);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $siblings = $itineraryItem->itineraryDay->items()->orderBy('sort_order')->get();
        $index = $siblings->search(fn ($item) => $item->id === $itineraryItem->id);

        $swapWith = $direction === 'up' ? $siblings->get($index - 1) : $siblings->get($index + 1);

        if ($swapWith) {
            [$orderA, $orderB] = [$itineraryItem->sort_order, $swapWith->sort_order];
            $itineraryItem->update(['sort_order' => $orderB]);
            $swapWith->update(['sort_order' => $orderA]);
        }

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', 'Item reordered.');
    }
}
