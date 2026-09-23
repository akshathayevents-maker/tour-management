<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['itinerary_day_id', 'title', 'time', 'notes', 'sort_order'])]
class ItineraryItem extends Model
{
    use BelongsToCompany, HasFactory;

    public function itineraryDay(): BelongsTo
    {
        return $this->belongsTo(ItineraryDay::class);
    }
}
