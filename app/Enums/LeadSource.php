<?php

namespace App\Enums;

/**
 * Where a lead came from. Backed enum instead of a lookup table: the set
 * changes rarely, needs no per-company customization yet, and this keeps
 * lead creation a single insert. Add a `channels` table later only if a
 * business genuinely needs custom sources.
 */
enum LeadSource: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case WhatsApp = 'whatsapp';
    case Referral = 'referral';
    case Website = 'website';
    case WalkIn = 'walk_in';
    case ExistingCustomer = 'existing_customer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook',
            self::WhatsApp => 'WhatsApp',
            self::Referral => 'Referral',
            self::Website => 'Website',
            self::WalkIn => 'Walk-in',
            self::ExistingCustomer => 'Existing Customer',
            self::Other => 'Other',
        };
    }
}
