<?php

namespace Database\Factories;

use App\Enums\QuotationLineCategory;
use App\Models\Company;
use App\Models\QuotationLineItem;
use App\Models\QuotationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationLineItem>
 */
class QuotationLineItemFactory extends Factory
{
    protected $model = QuotationLineItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'quotation_version_id' => QuotationVersion::factory(),
            'description' => fake()->words(2, true),
            'category' => QuotationLineCategory::Other,
            'sell_price' => fake()->randomFloat(2, 1000, 50000),
        ];
    }
}
