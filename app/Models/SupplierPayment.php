<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_item_id', 'amount', 'paid_at', 'method', 'reference', 'notes',
    'supplier_name_snapshot', 'supplier_phone_snapshot',
    'voided_at', 'voided_reason',
])]
class SupplierPayment extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupplierPayment $payment) {
            if (blank($payment->created_by) && auth()->check()) {
                $payment->created_by = auth()->id();
            }
        });
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function void(string $reason): void
    {
        $this->update(['voided_at' => now(), 'voided_reason' => $reason]);
    }
}
