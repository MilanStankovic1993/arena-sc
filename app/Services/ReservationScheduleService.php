<?php

namespace App\Services;

use App\Models\Court;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReservationScheduleService
{
    private const OPENING_TIME = '08:00:00';

    private const CLOSING_TIME = '23:00:00';

    public function buildDailySchedule(Court $court, Carbon $day, int $durationMinutes): Collection
    {
        $slots = collect();
        $opening = $day->copy()->setTimeFromTimeString(self::OPENING_TIME);
        $closing = $day->copy()->setTimeFromTimeString(self::CLOSING_TIME);

        for ($slot = $opening->copy(); $slot->copy()->addMinutes($durationMinutes)->lte($closing); $slot->addMinutes(30)) {
            $endsAt = $slot->copy()->addMinutes($durationMinutes);

            $slots->push([
                'starts_at' => $slot->copy(),
                'ends_at' => $endsAt->copy(),
            ]);
        }

        return $slots;
    }

    public function isWithinOperatingHours(CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        $opening = $startsAt->copy()->startOfDay()->setTimeFromTimeString(self::OPENING_TIME);
        $closing = $startsAt->copy()->startOfDay()->setTimeFromTimeString(self::CLOSING_TIME);

        return $endsAt->gt($startsAt)
            && $startsAt->gte($opening)
            && $endsAt->lte($closing);
    }
}
