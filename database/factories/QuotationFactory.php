<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Quotation;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'trip_id' => Trip::factory(),
        ];
    }
}
