<?php

namespace App\Enums;

enum SupplierType: string
{
    case Hotel = 'hotel';
    case Transport = 'transport';
    case Activity = 'activity';
    case Guide = 'guide';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hotel => 'Hotel',
            self::Transport => 'Transport',
            self::Activity => 'Activity',
            self::Guide => 'Guide',
            self::Other => 'Other',
        };
    }
}
