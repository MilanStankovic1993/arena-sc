<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\Sport;
use App\Services\ReservationAvailabilityService;
use App\Services\ReservationPricingService;
use App\Services\ReservationScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sports = Sport::query()
            ->where('is_active', true)
            ->with(['courts' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('sort_order')
            ->get();

        return view('booking.index', [
            'sports' => $sports,
            'initialState' => [
                'sport' => $request->string('sport')->toString(),
                'court' => $request->string('court')->toString(),
                'date' => Carbon::parse($request->input('date', now()->toDateString()))->format('Y-m-d'),
                'duration' => (int) $request->integer('duration', 60),
            ],
        ]);
    }

    public function availability(
        Request $request,
        ReservationAvailabilityService $availabilityService,
        ReservationPricingService $pricingService,
        ReservationScheduleService $scheduleService,
    ): JsonResponse {
        $sport = Sport::query()
            ->where('slug', $request->string('sport')->toString())
            ->where('is_active', true)
            ->first();

        abort_unless($sport, 404);

        $court = Court::query()
            ->where('slug', $request->string('court')->toString())
            ->where('sport_id', $sport->id)
            ->where('is_active', true)
            ->with('sport')
            ->first();

        abort_unless($court, 404);

        $selectedDay = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();
        $selectedDuration = max(60, min(120, (int) $request->integer('duration', 60)));

        $pricingRules = $court->sport->pricingRules()
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $slots = $scheduleService->buildDailySchedule($court, $selectedDay, $selectedDuration)
            ->map(function (array $slot) use ($court, $availabilityService, $pricingService) {
                $available = $availabilityService->isAvailable($court, $slot['starts_at'], $slot['ends_at']);

                return [
                    'starts_at' => $slot['starts_at']->format('Y-m-d H:i:s'),
                    'ends_at' => $slot['ends_at']->format('Y-m-d H:i:s'),
                    'label' => $slot['starts_at']->format('H:i') . ' - ' . $slot['ends_at']->format('H:i'),
                    'price' => $pricingService->calculateCourtPrice($court, $slot['starts_at'], $slot['ends_at']),
                    'available' => $available,
                ];
            })
            ->filter(fn (array $slot): bool => $slot['available'])
            ->values();

        $equipment = Equipment::query()
            ->where('is_active', true)
            ->where('is_rentable', true)
            ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $court->sport_id))
            ->orderBy('name')
            ->get()
            ->map(fn (Equipment $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->short_description,
                'price' => (float) $item->rental_price,
            ])
            ->values();

        return response()->json([
            'court' => [
                'id' => $court->id,
                'name' => $court->name,
                'description' => $court->description,
                'location' => $court->location,
                'surface' => $court->surface,
                'capacity' => $court->capacity,
                'sport' => $court->sport->name,
            ],
            'pricingRules' => $pricingRules->map(fn ($rule): array => [
                'name' => $rule->name,
                'days' => $rule->days_label,
                'time' => substr($rule->start_time, 0, 5) . ' - ' . substr($rule->end_time, 0, 5),
                'price60' => (float) $rule->price_60,
                'price90' => (float) $rule->price_90,
                'price120' => (float) $rule->price_120,
            ])->values(),
            'slots' => $slots,
            'equipment' => $equipment,
            'selectedDayLabel' => $selectedDay->format('d.m.Y'),
            'durationLabel' => match ($selectedDuration) {
                90 => '1,5h',
                120 => '2h',
                default => '1h',
            },
        ]);
    }
}
