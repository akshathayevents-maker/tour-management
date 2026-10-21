<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ItineraryTemplate;
use App\Models\ItineraryTemplateDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItineraryTemplateDay>
 */
class ItineraryTemplateDayFactory extends Factory
{
    protected $model = ItineraryTemplateDay::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'itinerary_template_id' => ItineraryTemplate::factory(),
            'day_number' => 1,
        ];
    }
}
