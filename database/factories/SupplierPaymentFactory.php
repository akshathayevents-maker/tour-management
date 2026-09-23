<?php

namespace Database\Factories;

use App\Models\BookingItem;
use App\Models\Company;
use App\Models\SupplierPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierPayment>
 */
class SupplierPaymentFactory extends Factory
{
    protected $model = SupplierPayment::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'booking_item_id' => BookingItem::factory(),
            'amount' => fake()->randomFloat(2, 1000, 20000),
            'paid_at' => now()->toDateString(),
        ];
    }
}
