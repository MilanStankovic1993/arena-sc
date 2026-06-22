<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\User;
use Illuminate\Support\Collection;

class UserStatisticsService
{
    public function summary(User $user): array
    {
        $reservations = $user->participatedReservations()
            ->with(['sport:id,name', 'court:id,name'])
            ->orderBy('starts_at')
            ->get();

        $total = $reservations->count();
        $owned = $reservations->where('user_id', $user->id)->count();
        $joined = max($total - $owned, 0);
        $reserved = $reservations->where('status', ReservationStatus::Reserved)->count();
        $cancelled = $reservations->where('status', ReservationStatus::Cancelled)->count();
        $revenue = (float) $reservations
            ->whereIn('status', [ReservationStatus::Reserved])
            ->sum('total_price');
        $durationMinutes = (int) $reservations->sum('duration_minutes');
        $averageSpend = $total > 0 ? $revenue / $total : 0.0;
        $cancellationRate = $total > 0 ? ($cancelled / $total) * 100 : 0.0;

        return [
            'total' => $total,
            'owned' => $owned,
            'joined' => $joined,
            'reserved' => $reserved,
            'cancelled' => $cancelled,
            'revenue' => $revenue,
            'averageSpend' => $averageSpend,
            'durationMinutes' => $durationMinutes,
            'durationHours' => round($durationMinutes / 60, 1),
            'cancellationRate' => $cancellationRate,
            'firstReservationAt' => $reservations->first()?->starts_at,
            'lastReservationAt' => $reservations->last()?->starts_at,
            'favoriteSport' => $this->favoriteLabel($reservations->countBy(fn ($reservation) => $reservation->sport?->name)),
            'favoriteCourt' => $this->favoriteLabel($reservations->countBy(fn ($reservation) => $reservation->court?->name)),
            'favoriteWeekday' => $this->favoriteLabel($reservations->countBy(fn ($reservation) => $reservation->starts_at?->locale('sr')->translatedFormat('D'))),
            'favoriteTime' => $this->favoriteLabel($reservations->countBy(fn ($reservation) => $reservation->starts_at?->format('H:00'))),
        ];
    }

    protected function favoriteLabel(Collection $counts): ?string
    {
        $filtered = $counts
            ->filter(fn ($value, $label) => filled($label))
            ->sortDesc();

        if ($filtered->isEmpty()) {
            return null;
        }

        $label = (string) $filtered->keys()->first();
        $count = (int) $filtered->first();

        return $label.' ('.$count.')';
    }
}
