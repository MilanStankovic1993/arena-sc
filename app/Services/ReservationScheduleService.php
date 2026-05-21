<?php

namespace App\Services;

use App\Models\Court;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReservationScheduleService
{
    public function buildDailySchedule(Court $court, Carbon $day, int $durationMinutes): Collection
    {
        $slots = collect();
        $opening = $day->copy()->setTime(8, 0);
        $closing = $day->copy()->setTime(23, 0);

        for ($slot = $opening->copy(); $slot->copy()->addMinutes($durationMinutes)->lte($closing); $slot->addMinutes(30)) {
            $endsAt = $slot->copy()->addMinutes($durationMinutes);

            $slots->push([
                'starts_at' => $slot->copy(),
                'ends_at' => $endsAt->copy(),
            ]);
        }

        return $slots;
    }
}
