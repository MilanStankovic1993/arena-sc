<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\User;
use App\Models\UserMembership;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class MembershipReservationLimitService
{
    public function canReserve(User $user, int $sportId, CarbonInterface $startsAt): bool
    {
        $membership = $user->activeMembershipForSport($sportId, $startsAt);

        if (! $membership) {
            return true;
        }

        return $this->reservationsForMembershipPeriod($user, $sportId, $membership) < $membership->membershipPlan->reservation_limit;
    }

    public function message(User $user, int $sportId, CarbonInterface $startsAt): string
    {
        $membership = $user->activeMembershipForSport($sportId, $startsAt);
        $limit = $membership?->membershipPlan?->reservation_limit ?? 0;

        return "Dostigli ste limit clanarine za ceo period ({$limit} termina).";
    }

    public function reservationsForMembershipPeriod(User $user, int $sportId, UserMembership $membership): int
    {
        $query = $user->participatedReservations()
            ->where('status', ReservationStatus::Reserved)
            ->whereBetween('starts_at', [
                Carbon::parse($membership->starts_at)->startOfDay(),
                Carbon::parse($membership->ends_at)->endOfDay(),
            ]);

        if ($membership->membershipPlan?->sport_id) {
            $query->where('sport_id', $sportId);
        }

        return $query->count();
    }
}
