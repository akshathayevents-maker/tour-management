<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reusable, destination-specific day-by-day plan. Generating a trip
 * itinerary from a template COPIES its days/items into real ItineraryDay/
 * ItineraryItem records (see ItineraryGenerationService) — editing the
 * generated copy never touches this record, and editing this template
 * never rewrites itineraries already generated from it.
 */
#[Fillable(['destination', 'name', 'description', 'is_active'])]
class ItineraryTemplate extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function days(): HasMany
    {
        return $this->hasMany(ItineraryTemplateDay::class)->orderBy('day_number');
    }

    public function durationInDays(): int
    {
        return $this->days->count();
    }
}
