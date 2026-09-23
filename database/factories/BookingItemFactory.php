<?php

namespace Database\Factories;

use App\Enums\BookingItemStatus;
use App\Enums\QuotationLineCategory;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingItem>
 */
class BookingItemFactory extends Factory
{
    protected $model = BookingItem::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'booking_id' => Booking::factory(),
            'description' => fake()->words(2, true),
            'category' => QuotationLineCategory::Other,
            'status' => BookingItemStatus::Pending,
        ];
    }
}
