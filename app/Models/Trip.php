<?php

namespace App\Models;

use App\Enums\TripStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'customer_id', 'enquiry_id', 'destination',
    'start_date', 'end_date', 'travellers_count', 'status',
])]
class Trip extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function itinerary(): HasOne
    {
        return $this->hasOne(Itinerary::class);
    }

    public function quotation(): HasOne
    {
        return $this->hasOne(Quotation::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->latest();
    }

    /**
     * A trip can technically have more than one Booking record (e.g. a
     * cancelled-then-rebooked trip), but the workspace only ever needs
     * the most relevant one to summarize.
     */
    public function activeBooking(): ?Booking
    {
        return $this->bookings->firstWhere('status', '!=', \App\Enums\BookingStatus::Cancelled)
            ?? $this->bookings->first();
    }

    public function followUps(): MorphMany
    {
        return $this->morphMany(FollowUp::class, 'followupable');
    }
}
