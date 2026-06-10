<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\User;
use Illuminate\Support\Collection;

class UserReservationStatsService
{
    public function recalculate(User|int $user): void
    {
        $user = $user instanceof User ? $user : User::query()->find($user);

        if (! $user) {
            return;
        }

        $reservations = $user->participatedReservations();

        $user->forceFill([
            'total_reservations' => (clone $reservations)->count(),
            'cancelled_reservations' => (clone $reservations)
                ->where('status', ReservationStatus::Cancelled->value)
                ->count(),
            'last_reservation_at' => (clone $reservations)->max('starts_at'),
        ])->saveQuietly();
    }

    public function recalculateMany(Collection|array $users): void
    {
        collect($users)
            ->filter()
            ->unique()
            ->each(fn (User|int $user): mixed => $this->recalculate($user));
    }
}
