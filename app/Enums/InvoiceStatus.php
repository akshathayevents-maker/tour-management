<?php

namespace App\Enums;

/**
 * No Draft: creating an invoice issues it immediately (Phase 4A keeps
 * this to one action — "Create Invoice"). Cancel is the only correction
 * path; a changed booking amount means issuing a new invoice, not
 * editing this one.
 */
enum InvoiceStatus: string
{
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Issued',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Issued => 'blue',
            self::Cancelled => 'slate',
        };
    }
}
