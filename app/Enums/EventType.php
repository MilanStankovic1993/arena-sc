<?php

namespace App\Enums;

enum EventType: string
{
    case Tournament = 'tournament';
    case League = 'league';

    public function label(): string
    {
        return match ($this) {
            self::Tournament => 'Turnir',
            self::League => 'Liga',
        };
    }
}
