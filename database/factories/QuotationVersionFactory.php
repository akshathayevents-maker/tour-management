<?php

namespace Database\Factories;

use App\Enums\QuotationVersionStatus;
use App\Models\Company;
use App\Models\Quotation;
use App\Models\QuotationVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuotationVersion>
 */
class QuotationVersionFactory extends Factory
{
    protected $model = QuotationVersion::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'quotation_id' => Quotation::factory(),
            'version_number' => 1,
            'status' => QuotationVersionStatus::Draft,
        ];
    }
}
