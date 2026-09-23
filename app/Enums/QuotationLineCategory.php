<?php

namespace App\Enums;

enum QuotationLineCategory: string
{
    case Hotel = 'hotel';
    case Transport = 'transport';
    case Activity = 'activity';
    case Food = 'food';
    case Package = 'package';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hotel => 'Hotel',
            self::Transport => 'Transport',
            self::Activity => 'Activity',
            self::Food => 'Food',
            self::Package => 'Package',
            self::Other => 'Other',
        };
    }
}
