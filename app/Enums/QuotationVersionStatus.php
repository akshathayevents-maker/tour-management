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

    /**
     * A version that was Accepted but was later replaced by a subsequently
     * accepted revision (see QuotationVersionService::accept()). Distinct
     * from Rejected (the customer never declined this version — it was
     * simply superseded) and from Sent (it's not awaiting a decision
     * anymore). Immutable, historically auditable, still printable —
     * exactly like every other decided version.
     */
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Superseded => 'Superseded',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Sent => 'blue',
            self::Accepted => 'green',
            self::Rejected => 'red',
            self::Superseded => 'purple',
        };
    }
}
