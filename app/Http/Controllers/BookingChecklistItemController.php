<?php

namespace App\Http\Controllers;

use App\Enums\ChecklistPhase;
use App\Http\Requests\BookingChecklistItem\StoreBookingChecklistItemRequest;
use App\Models\Booking;
use App\Models\BookingChecklistItem;
use Illuminate\Http\RedirectResponse;

class BookingChecklistItemController extends Controller
{
    public function store(StoreBookingChecklistItemRequest $request, Booking $booking): RedirectResponse
    {
        $nextOrder = ($booking->checklistItems()->max('sort_order') ?? -1) + 1;

        $booking->checklistItems()->create([
            'label' => $request->string('label')->value(),
            'phase' => $request->filled('phase') ? $request->string('phase')->value() : ChecklistPhase::BeforeTrip,
            'sort_order' => $nextOrder,
        ]);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Checklist item added.');
    }

    public function complete(BookingChecklistItem $bookingChecklistItem): RedirectResponse
    {
        $booking = $bookingChecklistItem->booking;
        $this->authorize('update', $booking);

        $bookingChecklistItem->update([
            'done_at' => now(),
            'done_by' => request()->user()->id,
        ]);

        activity()->performedOn($booking)->causedBy(request()->user())
            ->log("Checklist item \"{$bookingChecklistItem->label}\" completed.");

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Checklist item completed.');
    }
}
