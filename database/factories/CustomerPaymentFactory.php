<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\Company;
use App\Models\CustomerPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerPayment>
 */
class CustomerPaymentFactory extends Factory
{
    protected $model = CustomerPayment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'booking_id' => Booking::factory(),
            'amount' => fake()->randomFloat(2, 1000, 20000),
            'method' => PaymentMethod::Cash,
            'paid_at' => now()->toDateString(),
        ];
    }
}
