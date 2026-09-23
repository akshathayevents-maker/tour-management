<?php

namespace App\Enums;

enum ChecklistPhase: string
{
    case BeforeTrip = 'before_trip';
    case DuringTrip = 'during_trip';
    case AfterTrip = 'after_trip';

    public function label(): string
    {
        return match ($this) {
            self::BeforeTrip => 'Before trip',
            self::DuringTrip => 'During trip',
            self::AfterTrip => 'After trip',
        };
    }
}
