<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Na cekanju',
            self::Approved => 'Odobrena',
            self::Completed => 'Realizovana',
            self::Cancelled => 'Otkazana',
            self::Rejected => 'Odbijena',
        };
    }
}
