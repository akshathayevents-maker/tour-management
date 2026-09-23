<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceLineItem>
 */
class InvoiceLineItemFactory extends Factory
{
    protected $model = InvoiceLineItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'invoice_id' => Invoice::factory(),
            'description' => fake()->words(2, true),
            'amount' => fake()->randomFloat(2, 1000, 20000),
        ];
    }
}
