<?php

namespace App\Models;

use App\Enums\BookingItemStatus;
use App\Enums\BookingStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'customer_id', 'trip_id', 'accepted_quotation_version_id', 'status',
    'confirmed_at', 'completed_at', 'cancelled_at', 'cancellation_reason',
])]
class Booking extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (blank($booking->confirmed_at) && $booking->status === BookingStatus::Confirmed) {
                $booking->confirmed_at = now();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function acceptedQuotationVersion(): BelongsTo
    {
        return $this->belongsTo(QuotationVersion::class, 'accepted_quotation_version_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class)->orderBy('id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(BookingChecklistItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class)->orderByDesc('paid_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderByDesc('issued_at');
    }

    /**
     * Derived, never stored — the trip is "in progress" only while it's
     * genuinely running and the booking hasn't been cancelled.
     */
    public function isInProgress(): bool
    {
        if ($this->status !== BookingStatus::Confirmed) {
            return false;
        }

        $trip = $this->trip;

        if (! $trip?->start_date) {
            return false;
        }

        $end = $trip->end_date ?? $trip->start_date;

        return now()->between($trip->start_date->startOfDay(), $end->endOfDay());
    }

    public function totalSellPrice(): float
    {
        return (float) $this->items->sum('sell_price');
    }

    /**
     * Sum of valid (non-voided) customer payments — derived, never
     * stored, so it can never drift from the actual payment records.
     */
    public function totalPaid(): float
    {
        return (float) $this->payments->whereNull('voided_at')->sum('amount');
    }

    public function amountRemaining(): float
    {
        return $this->totalSellPrice() - $this->totalPaid();
    }

    public function isFullyPaid(): bool
    {
        return $this->totalSellPrice() > 0 && $this->amountRemaining() <= 0.0;
    }

    /**
     * Once Completed or Cancelled, operational data (items, supplier
     * assignments, statuses) freezes — only the checklist stays editable,
     * since post-trip items like "feedback requested" are meant to happen
     * after the booking closes.
     */
    public function isOperationallyLocked(): bool
    {
        return $this->status !== BookingStatus::Confirmed;
    }

    /**
     * Read-model for "what's still missing" — status indicators, not a
     * score, composed at render time from data that already has a home
     * (items, checklist). Nothing here is stored.
     *
     * @return array<int, array{label: string, state: string, ok: bool}>
     */
    public function readinessRows(): array
    {
        $rows = [[
            'label' => 'Customer confirmation',
            'state' => $this->status->label(),
            'ok' => $this->status !== BookingStatus::Cancelled,
        ]];

        $active = $this->items->where('status', '!=', BookingItemStatus::Cancelled);

        foreach ($active->groupBy(fn ($item) => $item->category?->label() ?? 'Other') as $label => $items) {
            $state = match (true) {
                $items->contains('status', BookingItemStatus::Pending) => BookingItemStatus::Pending,
                $items->contains('status', BookingItemStatus::Requested) => BookingItemStatus::Requested,
                default => BookingItemStatus::Confirmed,
            };

            $rows[] = ['label' => $label, 'state' => $state->label(), 'ok' => $state === BookingItemStatus::Confirmed];
        }

        $total = $this->checklistItems->count();
        $done = $this->checklistItems->filter->isDone()->count();

        $rows[] = [
            'label' => 'Checklist',
            'state' => "{$done} / {$total} completed",
            'ok' => $total > 0 && $done === $total,
        ];

        return $rows;
    }
}
