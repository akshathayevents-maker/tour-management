<?php

namespace App\Enums;

/**
 * Deliberately no Draft or InProgress case: a Booking's existence means
 * the customer has confirmed. "In progress" is derived from Trip dates +
 * this status (see Booking::isInProgress()), never stored.
 */
enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Confirmed => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'slate',
        };
    }
}
