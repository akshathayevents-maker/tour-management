<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 50000);

        return [
            'company_id' => Company::factory(),
            'booking_id' => Booking::factory(),
            'invoice_number' => 'INV-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'issued_at' => now()->toDateString(),
            'status' => InvoiceStatus::Issued,
            'subtotal' => $subtotal,
            'total' => $subtotal,
            'customer_name_snapshot' => fake()->name(),
        ];
    }
}
