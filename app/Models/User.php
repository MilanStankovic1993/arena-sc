<?php

namespace App\Models;

use App\Enums\UserRole;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
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

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $wasSuperAdmin = $user->exists
                && UserRole::tryFrom((string) $user->getRawOriginal('role')) === UserRole::SuperAdmin;

            if ($wasSuperAdmin
                && $user->role !== UserRole::SuperAdmin
                && ! static::query()
                    ->where('role', UserRole::SuperAdmin->value)
                    ->whereKeyNot($user->getKey())
                    ->exists()) {
                throw ValidationException::withMessages([
                    'role' => 'Poslednjem superadmin nalogu nije moguce promeniti ulogu.',
                ]);
            }
        });

        static::deleting(function (User $user): void {
            if ($user->role === UserRole::SuperAdmin
                && ! static::query()
                    ->where('role', UserRole::SuperAdmin->value)
                    ->whereKeyNot($user->getKey())
                    ->exists()) {
                throw ValidationException::withMessages([
                    'user' => 'Poslednji superadmin nalog nije moguce obrisati.',
                ]);
            }
        });
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function participatedReservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'reservation_participants')->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UserMembership::class);
    }

    public function latestMembership(): HasOne
    {
        return $this->hasOne(UserMembership::class)->latestOfMany('ends_at');
    }

    public function activeMembershipForSport(int $sportId, CarbonInterface|string|null $date = null): ?UserMembership
    {
        $date = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        return $this->memberships()
            ->with('membershipPlan')
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', $date)
            ->whereDate('ends_at', '>=', $date)
            ->whereHas('membershipPlan', function ($query) use ($sportId): void {
                $query
                    ->where('is_active', true)
                    ->where(fn ($builder) => $builder->whereNull('sport_id')->orWhere('sport_id', $sportId));
            })
            ->get()
            ->sort(function (UserMembership $first, UserMembership $second) use ($sportId): int {
                $firstSpecific = (int) ($first->membershipPlan?->sport_id === $sportId);
                $secondSpecific = (int) ($second->membershipPlan?->sport_id === $sportId);

                if ($firstSpecific !== $secondSpecific) {
                    return $secondSpecific <=> $firstSpecific;
                }

                return $second->ends_at->timestamp <=> $first->ends_at->timestamp;
            })
            ->first();
    }

    public function getActiveMembershipLabelAttribute(): string
    {
        $membership = $this->memberships()
            ->with('membershipPlan')
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', now()->toDateString())
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->orderByDesc('ends_at')
            ->first();

        if (! $membership) {
            return 'Nema članarinu';
        }

        return $membership->membershipPlan->name.' do '.$membership->ends_at->format('d.m.Y');
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
