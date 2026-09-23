<?php

namespace Database\Factories;

use App\Enums\ChecklistPhase;
use App\Models\Booking;
use App\Models\BookingChecklistItem;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingChecklistItem>
 */
class BookingChecklistItemFactory extends Factory
{
    protected $model = BookingChecklistItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'booking_id' => Booking::factory(),
            'label' => fake()->sentence(3),
            'phase' => ChecklistPhase::BeforeTrip,
            'sort_order' => 0,
        ];
    }
}
