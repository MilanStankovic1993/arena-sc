<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Collection;

class ReservationParticipantService
{
    public function attachUsers(Reservation $reservation, Collection|array $users): int
    {
        $before = $reservation->participants()->pluck('users.id');
        $ids = $this->normalizeUserIds($users)->push($reservation->user_id)->filter()->unique()->values();

        $reservation->participants()->syncWithoutDetaching($ids->all());

        $this->recalculate($before->merge($ids));

        return $ids->diff($before)->count();
    }

    public function syncUsers(Reservation $reservation, Collection|array $users): void
    {
        $before = $reservation->participants()->pluck('users.id');
        $ids = $this->normalizeUserIds($users)->push($reservation->user_id)->filter()->unique()->values();

        $reservation->participants()->sync($ids->all());

        $this->recalculate($before->merge($ids));
    }

    protected function normalizeUserIds(Collection|array $users): Collection
    {
        return collect($users)
            ->map(fn (User|int|string $user): int => $user instanceof User ? $user->id : (int) $user)
            ->filter()
            ->unique()
            ->values();
    }

    protected function recalculate(Collection $userIds): void
    {
        app(UserReservationStatsService::class)->recalculateMany($userIds);
    }
}
