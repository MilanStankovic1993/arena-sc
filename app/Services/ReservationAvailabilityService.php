<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Models\Reservation;
use Carbon\CarbonInterface;

class ReservationAvailabilityService
{
    public function isAvailable(Court $court, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreReservationId = null): bool
    {
        $hasClosure = $court->closures()
            ->where('is_active', true)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        if ($hasClosure) {
            return false;
        }

        return ! Reservation::query()
            ->where('court_id', $court->id)
            ->when($ignoreReservationId, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            ->whereIn('status', [
                ReservationStatus::Pending->value,
                ReservationStatus::Approved->value,
                ReservationStatus::Completed->value,
            ])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }
}
