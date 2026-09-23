<?php

namespace App\Models;

use App\Enums\BookingItemStatus;
use App\Enums\QuotationLineCategory;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'supplier_id', 'description', 'category', 'sell_price', 'cost',
    'start_at', 'end_at', 'confirmation_number', 'status', 'notes',
    'supplier_name_snapshot', 'supplier_phone_snapshot',
    'cancelled_at', 'cancellation_reason',
])]
class BookingItem extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'category' => QuotationLineCategory::class,
            'status' => BookingItemStatus::class,
            'sell_price' => 'decimal:2',
            'cost' => 'decimal:2',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
