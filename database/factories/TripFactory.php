<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_id' => Customer::factory(),
            'destination' => fake()->city(),
            'status' => TripStatus::Planning,
        ];
    }
}
