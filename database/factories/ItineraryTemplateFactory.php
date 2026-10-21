<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ItineraryTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItineraryTemplate>
 */
class ItineraryTemplateFactory extends Factory
{
    protected $model = ItineraryTemplate::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'destination' => fake()->city(),
            'name' => fake()->words(3, true).' itinerary',
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
