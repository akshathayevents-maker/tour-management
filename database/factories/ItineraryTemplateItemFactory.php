<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ItineraryTemplateDay;
use App\Models\ItineraryTemplateItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItineraryTemplateItem>
 */
class ItineraryTemplateItemFactory extends Factory
{
    protected $model = ItineraryTemplateItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'itinerary_template_day_id' => ItineraryTemplateDay::factory(),
            'title' => fake()->sentence(3),
            'sort_order' => 0,
        ];
    }
}
