<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ItineraryDay;
use App\Models\ItineraryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItineraryItem>
 */
class ItineraryItemFactory extends Factory
{
    protected $model = ItineraryItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'itinerary_day_id' => ItineraryDay::factory(),
            'title' => fake()->sentence(3),
            'sort_order' => 0,
        ];
    }
}
