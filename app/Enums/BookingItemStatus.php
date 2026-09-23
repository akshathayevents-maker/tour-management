<?php

namespace App\Enums;

/**
 * One shared lifecycle across every service category (hotel, transport,
 * activity, ...) — no per-category status lists. "Completed" is
 * deliberately absent: execution is tracked at the Booking/Trip level,
 * not per item.
 */
enum BookingItemStatus: string
{
    case Pending = 'pending';
    case Requested = 'requested';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Requested => 'Requested',
            self::Confirmed => 'Confirmed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'slate',
            self::Requested => 'amber',
            self::Confirmed => 'green',
            self::Cancelled => 'red',
        };
    }
}
