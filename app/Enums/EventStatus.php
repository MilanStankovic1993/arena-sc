<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Registration = 'registration';
    case Ongoing = 'ongoing';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'U pripremi',
            self::Registration => 'Prijave otvorene',
            self::Ongoing => 'U toku',
            self::Completed => 'Zavrseno',
        };
    }
}
