<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Court;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ReservationAvailabilityService
{
    public function isAvailable(Court $court, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreReservationId = null): bool
    {
        return ! $this->closures($court, $startsAt, $endsAt)->exists()
            && ! $this->reservations($court, $startsAt, $endsAt, $ignoreReservationId)->exists();
    }

    public function markAvailableSlots(Court $court, Collection $slots, ?int $ignoreReservationId = null): Collection
    {
        if ($slots->isEmpty()) {
            return $slots;
        }

        $first = $slots->first()['starts_at'];
        $last = $slots->last()['ends_at'];
        $columns = ['starts_at', 'ends_at'];
        $blocked = $this->closures($court, $first, $last)->get($columns)->toBase()
            ->concat($this->reservations($court, $first, $last, $ignoreReservationId)->get($columns));

        return $slots->map(fn (array $slot): array => $slot + [
            'available' => ! $blocked->contains(fn ($interval): bool =>
                $interval->starts_at->lt($slot['ends_at']) && $interval->ends_at->gt($slot['starts_at'])),
        ]);
    }

    private function closures(Court $court, CarbonInterface $startsAt, CarbonInterface $endsAt): HasMany
    {
        return $court->closures()->where('is_active', true)
            ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
    }

    private function reservations(Court $court, CarbonInterface $startsAt, CarbonInterface $endsAt, ?int $ignoreReservationId): HasMany
    {
        return $court->reservations()
            ->when($ignoreReservationId, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            ->where('status', ReservationStatus::Reserved->value)
            ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt);
    }
}
