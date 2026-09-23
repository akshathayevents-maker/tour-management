<?php

namespace App\Models;

use App\Enums\BookingItemStatus;
use App\Enums\QuotationLineCategory;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function supplierPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class)->orderByDesc('paid_at');
    }

    /**
     * Sum of valid (non-voided) supplier payments — derived, never
     * stored, mirroring Booking::totalPaid() for customer payments.
     */
    public function totalPaidToSupplier(): float
    {
        return (float) $this->supplierPayments->whereNull('voided_at')->sum('amount');
    }

    public function amountPayable(): ?float
    {
        if ($this->cost === null) {
            return null;
        }

        return (float) $this->cost - $this->totalPaidToSupplier();
    }

    public function isFullyPaidToSupplier(): bool
    {
        return $this->cost !== null && $this->amountPayable() <= 0.0;
    }
}
