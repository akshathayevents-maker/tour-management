<?php

namespace Database\Factories;

use App\Enums\EnquiryStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enquiry>
 */
class EnquiryFactory extends Factory
{
    protected $model = Enquiry::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'destination' => fake()->city(),
            'status' => EnquiryStatus::New,
        ];
    }
}
