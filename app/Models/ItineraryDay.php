<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['itinerary_id', 'day_number', 'date', 'destination', 'notes', 'source_itinerary_template_day_id'])]
class ItineraryDay extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItineraryItem::class)->orderBy('sort_order');
    }

    /**
     * Traceability only — which template day this was generated from, if
     * any. Never used to re-sync content; see ItineraryTemplate docblock.
     */
    public function sourceTemplateDay(): BelongsTo
    {
        return $this->belongsTo(ItineraryTemplateDay::class, 'source_itinerary_template_day_id');
    }
}
