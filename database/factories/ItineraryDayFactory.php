<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Itinerary;
use App\Models\ItineraryDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItineraryDay>
 */
class ItineraryDayFactory extends Factory
{
    protected $model = ItineraryDay::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'itinerary_id' => Itinerary::factory(),
            'day_number' => 1,
        ];
    }
}
