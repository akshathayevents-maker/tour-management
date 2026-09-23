<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Itinerary;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Itinerary>
 */
class ItineraryFactory extends Factory
{
    protected $model = Itinerary::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'trip_id' => Trip::factory(),
        ];
    }
}
