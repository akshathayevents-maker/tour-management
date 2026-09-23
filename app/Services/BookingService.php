<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\QuotationVersion;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

/**
 * Owns Booking creation from both entry points (accepted quotation, or
 * direct/manual) so checklist seeding always happens exactly once, in
 * one place, regardless of how the booking came to exist.
 */
class BookingService
{
    public function __construct(private BookingChecklistService $checklist) {}

    public function createFromQuotationVersion(QuotationVersion $version): Booking
    {
        return DB::transaction(function () use ($version) {
            $trip = $version->quotation->trip;

            $booking = Booking::create([
                'customer_id' => $trip->customer_id,
                'trip_id' => $trip->id,
                'accepted_quotation_version_id' => $version->id,
            ]);

            $this->checklist->seedDefaults($booking);

            return $booking;
        });
    }

    public function createDirect(Trip $trip): Booking
    {
        return DB::transaction(function () use ($trip) {
            $booking = Booking::create([
                'customer_id' => $trip->customer_id,
                'trip_id' => $trip->id,
            ]);

            $this->checklist->seedDefaults($booking);

            return $booking;
        });
    }
}
