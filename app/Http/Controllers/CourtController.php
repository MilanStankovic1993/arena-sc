<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Equipment;
use App\Services\ReservationAvailabilityService;
use App\Services\ReservationPricingService;
use App\Services\ReservationScheduleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourtController extends Controller
{
    public function show(
        Request $request,
        Court $court,
        ReservationAvailabilityService $availabilityService,
        ReservationPricingService $pricingService,
        ReservationScheduleService $scheduleService,
    ): View
    {
        $court->load(['sport', 'pricingRules' => fn ($query) => $query->where('is_active', true)->orderBy('start_time')]);
        $selectedDay = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();
        $duration = (int) $request->integer('duration', 60);
        $selectedSlot = $request->string('slot')->toString();

        $dailySlots = $scheduleService->buildDailySchedule($court, $selectedDay, $duration)
            ->map(function (array $slot) use ($court, $availabilityService, $pricingService) {
                $slot['available'] = $availabilityService->isAvailable($court, $slot['starts_at'], $slot['ends_at']);
                $slot['price'] = $pricingService->calculateCourtPrice($court, $slot['starts_at'], $slot['ends_at']);
                $slot['key'] = $slot['starts_at']->format('Y-m-d\TH:i');

                return $slot;
            });

        $selectedSlotData = $dailySlots->firstWhere('key', $selectedSlot);

        return view('courts.show', [
            'court' => $court,
            'equipment' => Equipment::query()
                ->where('is_active', true)
                ->where('is_rentable', true)
                ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $court->sport_id))
                ->get(),
            'selectedDay' => $selectedDay,
            'selectedDuration' => $duration,
            'dailySlots' => $dailySlots,
            'selectedSlotData' => $selectedSlotData,
        ]);
    }
}
