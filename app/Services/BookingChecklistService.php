<?php

namespace App\Services;

use App\Enums\ChecklistPhase;
use App\Models\Booking;

/**
 * Owns the default checklist definition, so seeding logic lives in one
 * place and can later be swapped for a company-specific template lookup
 * without touching Booking creation itself.
 */
class BookingChecklistService
{
    /**
     * @return array<int, array{label: string, phase: ChecklistPhase}>
     */
    public function defaults(): array
    {
        return [
            ['label' => 'Hotel confirmation', 'phase' => ChecklistPhase::BeforeTrip],
            ['label' => 'Transport confirmation', 'phase' => ChecklistPhase::BeforeTrip],
            ['label' => 'Activity confirmation', 'phase' => ChecklistPhase::BeforeTrip],
            ['label' => 'Customer itinerary sent', 'phase' => ChecklistPhase::BeforeTrip],
            ['label' => 'Customer payment check', 'phase' => ChecklistPhase::BeforeTrip],
            ['label' => 'Pickup confirmed', 'phase' => ChecklistPhase::DuringTrip],
            ['label' => 'Hotel check-in confirmed', 'phase' => ChecklistPhase::DuringTrip],
            ['label' => 'Trip completed', 'phase' => ChecklistPhase::AfterTrip],
            ['label' => 'Supplier balances reviewed', 'phase' => ChecklistPhase::AfterTrip],
            ['label' => 'Customer feedback requested', 'phase' => ChecklistPhase::AfterTrip],
        ];
    }

    public function seedDefaults(Booking $booking): void
    {
        foreach ($this->defaults() as $index => $item) {
            $booking->checklistItems()->create([
                'label' => $item['label'],
                'phase' => $item['phase'],
                'sort_order' => $index,
            ]);
        }
    }
}
