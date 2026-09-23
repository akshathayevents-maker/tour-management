<?php

namespace App\Models;

use App\Enums\QuotationLineCategory;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quotation_version_id', 'description', 'category', 'cost', 'sell_price', 'sort_order'])]
class QuotationLineItem extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'category' => QuotationLineCategory::class,
            'cost' => 'decimal:2',
            'sell_price' => 'decimal:2',
        ];
    }

    public function quotationVersion(): BelongsTo
    {
        return $this->belongsTo(QuotationVersion::class);
    }
}
