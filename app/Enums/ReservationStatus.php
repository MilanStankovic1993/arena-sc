<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Reserved = 'reserved';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Rezervisana',
            self::Cancelled => 'Otkazana',
        };
    }
}
