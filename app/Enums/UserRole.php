<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::Customer => 'Korisnik',
        };
    }
}
