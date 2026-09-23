<?php

namespace App\Enums;

enum TripStatus: string
{
    case Planning = 'planning';
    case Ready = 'ready';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Ready => 'Ready',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Planning => 'amber',
            self::Ready => 'blue',
            self::Completed => 'green',
            self::Cancelled => 'slate',
        };
    }
}
