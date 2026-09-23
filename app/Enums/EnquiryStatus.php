<?php

namespace App\Enums;

/**
 * Deliberately excludes quotation-stage statuses (Quotation Pending/Sent) —
 * those belong to the future Quotation module and would be fake states
 * with nothing behind them until that module exists.
 */
enum EnquiryStatus: string
{
    case New = 'new';
    case Planning = 'planning';
    case Confirmed = 'confirmed';
    case Lost = 'lost';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Planning => 'Planning',
            self::Confirmed => 'Confirmed',
            self::Lost => 'Lost',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Planning => 'amber',
            self::Confirmed => 'green',
            self::Lost, self::Cancelled => 'slate',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Confirmed, self::Lost, self::Cancelled], true);
    }
}
