<?php

namespace App\Enums;

/**
 * Deliberately has no "Expired" case: expiry is derived from valid_until
 * (see QuotationVersion::isExpired()), not stored — a stored Expired status
 * would need a cron/job to flip it and could drift from valid_until.
 */
enum QuotationVersionStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Sent => 'blue',
            self::Accepted => 'green',
            self::Rejected => 'red',
        };
    }
}
