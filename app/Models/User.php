<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'registered_at',
        'total_reservations',
        'cancelled_reservations',
        'last_reservation_at',
        'notes',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'registered_at' => 'date',
            'last_reservation_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
