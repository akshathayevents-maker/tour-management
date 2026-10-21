<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['itinerary_template_id', 'day_number', 'notes'])]
class ItineraryTemplateDay extends Model
{
    use BelongsToCompany, HasFactory;

    public function template(): BelongsTo
    {
        return $this->belongsTo(ItineraryTemplate::class, 'itinerary_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItineraryTemplateItem::class)->orderBy('sort_order');
    }
}
