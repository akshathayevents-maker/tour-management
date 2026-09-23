<?php

namespace App\Models;

use App\Enums\QuotationVersionStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quotation_id', 'version_number', 'status', 'sent_at', 'valid_until', 'terms', 'notes'])]
class QuotationVersion extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => QuotationVersionStatus::class,
            'sent_at' => 'datetime',
            'valid_until' => 'date',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(QuotationLineItem::class)->orderBy('sort_order');
    }

    /**
     * Once sent, a version is a record of what the customer was actually
     * shown — editing it after the fact would silently rewrite history.
     * Changes go into a new version instead (see QuotationVersionService).
     */
    public function isEditable(): bool
    {
        return $this->status === QuotationVersionStatus::Draft;
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null
            && $this->valid_until->isPast()
            && $this->status === QuotationVersionStatus::Sent;
    }

    public function totalSellPrice(): float
    {
        return (float) $this->lineItems->sum('sell_price');
    }

    public function totalCost(): ?float
    {
        if ($this->lineItems->every(fn ($item) => $item->cost === null)) {
            return null;
        }

        return (float) $this->lineItems->sum(fn ($item) => $item->cost ?? 0);
    }

    public function estimatedMargin(): ?float
    {
        $cost = $this->totalCost();

        return $cost === null ? null : $this->totalSellPrice() - $cost;
    }
}
