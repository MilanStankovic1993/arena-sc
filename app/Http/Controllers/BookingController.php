<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtClosure;
use App\Models\Equipment;
use App\Models\PricingRule;
use App\Models\Reservation;
use App\Models\Sport;
use App\Services\ReservationScheduleService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
                'date' => Carbon::parse($request->input('date', now()->toDateString()))->format('Y-m-d'),
            ],
        ]);
    }

    public function availability(
        Request $request,
        ReservationScheduleService $scheduleService,
    ): JsonResponse {
        $sport = Sport::query()
            ->where('slug', $request->string('sport')->toString())
            ->where('is_active', true)
            ->first();

        abort_unless($sport, 404);

        if (! $sport->supports_online_booking) {
            return response()->json([
                'sport' => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'cover_image_url' => $sport->cover_image_url,
                    'supports_online_booking' => false,
                ],
                'pricingRules' => [],
                'days' => [],
                'equipment' => [],
                'contact_only' => true,
                'contact_message' => "Za sport {$sport->name} online rezervacija nije dostupna. Kontaktirajte nas i zakazacemo termin.",
                'contact_phone' => config('arena.contact.phone'),
                'contact_email' => config('arena.contact.email'),
            ]);
        }

        $courts = Court::query()
            ->where('sport_id', $sport->id)
            ->where('is_active', true)
            ->with('sport')
            ->orderBy('name')
            ->get();

        abort_if($courts->isEmpty(), 404);

        $selectedDay = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();
        $periodStart = $selectedDay->copy()->startOfDay();
        $periodEnd = $selectedDay->copy()->addDays(2)->endOfDay();

        $cacheKey = 'booking.availability.' . $sport->id . '.' . $selectedDay->toDateString();

        $payload = Cache::remember($cacheKey, now()->addSeconds(20), function () use ($sport, $courts, $scheduleService, $selectedDay, $periodStart, $periodEnd): array {
            $pricingRules = $sport->pricingRules()
                ->where('is_active', true)
                ->orderBy('start_time')
                ->get();

            $courtMeta = $courts
                ->mapWithKeys(fn (Court $court): array => [
                    $court->id => [
                        'id' => $court->id,
                        'name' => $court->name,
                        'slug' => $court->slug,
                        'location' => $court->location,
                        'surface' => $court->surface,
                        'description' => $court->description,
                        'image_url' => $court->image_url,
                    ],
                ]);

            $reservationsByCourt = Reservation::query()
                ->whereIn('court_id', $courts->pluck('id'))
                ->where('status', 'reserved')
                ->where('starts_at', '<', $periodEnd)
                ->where('ends_at', '>', $periodStart)
                ->get(['court_id', 'starts_at', 'ends_at'])
                ->groupBy('court_id');

            $closuresByCourt = CourtClosure::query()
                ->whereIn('court_id', $courts->pluck('id'))
                ->where('is_active', true)
                ->where('starts_at', '<', $periodEnd)
                ->where('ends_at', '>', $periodStart)
                ->get(['court_id', 'starts_at', 'ends_at'])
                ->groupBy('court_id');

            $now = now();

            $days = collect(range(0, 2))
                ->map(function (int $offset) use ($selectedDay, $courts, $scheduleService, $pricingRules, $reservationsByCourt, $closuresByCourt, $now): array {
                $day = $selectedDay->copy()->addDays($offset);

                $times = $scheduleService->buildDailySchedule($courts->first(), $day, 60)
                    ->map(function (array $slot) use ($courts, $pricingRules, $reservationsByCourt, $closuresByCourt, $now) {
                        if ($slot['starts_at']->lte($now)) {
                            return null;
                        }

                        $durations = collect([60, 90, 120])
                            ->map(function (int $minutes) use ($slot, $courts, $pricingRules, $reservationsByCourt, $closuresByCourt) {
                                $startsAt = $slot['starts_at']->copy();
                                $endsAt = $startsAt->copy()->addMinutes($minutes);

                                $availableCourts = $courts
                                    ->filter(fn (Court $court): bool => $this->courtIsAvailableFromLoadedData(
                                        $court,
                                        $startsAt,
                                        $endsAt,
                                        $reservationsByCourt,
                                        $closuresByCourt,
                                    ))
                                    ->map(function (Court $court) use ($pricingRules, $startsAt, $endsAt): ?array {
                                        $rule = $this->matchingPricingRule($pricingRules, $court, $startsAt, $endsAt);

                                        if (! $rule) {
                                            return null;
                                        }

                                        $price = $rule->priceForDuration((int) $startsAt->diffInMinutes($endsAt));

                                        return [
                                            'id' => $court->id,
                                            'name' => $court->name,
                                            'price' => $price,
                                            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                                            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                                        ];
                                    })
                                    ->filter()
                                    ->values();

                                if ($availableCourts->isEmpty()) {
                                    return null;
                                }

                                return [
                                    'minutes' => $minutes,
                                    'label' => match ($minutes) {
                                        90 => '1.5 sata',
                                        120 => '2 sata',
                                        default => '1 sat',
                                    },
                                    'price_from' => $availableCourts->min('price'),
                                    'courts' => $availableCourts,
                                ];
                            })
                            ->filter()
                            ->values();

                        if ($durations->isEmpty()) {
                            return null;
                        }

                        return [
                            'time' => $slot['starts_at']->format('H:i'),
                            'starts_at' => $slot['starts_at']->format('Y-m-d H:i:s'),
                            'durations' => $durations,
                        ];
                    })
                    ->filter()
                    ->values();

                return [
                    'date' => $day->format('Y-m-d'),
                    'day_label' => $day->translatedFormat('D'),
                    'date_label' => $day->format('d'),
                    'month_label' => $day->translatedFormat('M'),
                    'full_label' => $day->format('d.m.Y'),
                    'times' => $times,
                ];
            })
            ->values();

            $equipment = Equipment::query()
                ->where('is_active', true)
                ->where('is_rentable', true)
                ->where(fn ($query) => $query->whereNull('sport_id')->orWhere('sport_id', $sport->id))
                ->orderBy('name')
                ->get()
                ->map(fn (Equipment $item): array => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->short_description,
                    'price' => (float) $item->rental_price,
                    'image_url' => $item->image_url,
                ])
                ->values();

            return [
                'sport' => [
                    'id' => $sport->id,
                    'name' => $sport->name,
                    'cover_image_url' => $sport->cover_image_url,
                    'supports_online_booking' => true,
                ],
                'courts' => $courtMeta,
                'pricingRules' => $pricingRules->map(fn ($rule): array => [
                    'name' => $rule->name,
                    'days' => $rule->days_label,
                    'time' => substr($rule->start_time, 0, 5) . ' - ' . substr($rule->end_time, 0, 5),
                    'price60' => (float) $rule->price_60,
                    'price90' => (float) $rule->price_90,
                    'price120' => (float) $rule->price_120,
                ])->values(),
                'days' => $days,
                'equipment' => $equipment,
            ];
        });

        return response()->json($payload);
    }

    protected function courtIsAvailableFromLoadedData(
        Court $court,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        Collection $reservationsByCourt,
        Collection $closuresByCourt,
    ): bool {
        $hasClosure = $closuresByCourt
            ->get($court->id, collect())
            ->contains(fn (CourtClosure $closure): bool => $this->periodsOverlap($closure->starts_at, $closure->ends_at, $startsAt, $endsAt));

        if ($hasClosure) {
            return false;
        }

        return ! $reservationsByCourt
            ->get($court->id, collect())
            ->contains(fn (Reservation $reservation): bool => $this->periodsOverlap($reservation->starts_at, $reservation->ends_at, $startsAt, $endsAt));
    }

    protected function matchingPricingRule(Collection $pricingRules, Court $court, CarbonInterface $startsAt, CarbonInterface $endsAt): ?PricingRule
    {
        return $pricingRules
            ->first(function (PricingRule $rule) use ($court, $startsAt, $endsAt): bool {
                if ((int) $rule->sport_id !== (int) $court->sport_id) {
                    return false;
                }

                $days = collect($rule->days_of_week ?? [])->map(fn ($day): int => (int) $day);

                if ($days->isNotEmpty() && ! $days->contains((int) $startsAt->dayOfWeek)) {
                    return false;
                }

                if ($rule->valid_from && $startsAt->toDateString() < $rule->valid_from->toDateString()) {
                    return false;
                }

                if ($rule->valid_to && $startsAt->toDateString() > $rule->valid_to->toDateString()) {
                    return false;
                }

                return substr((string) $rule->start_time, 0, 8) <= $startsAt->format('H:i:s')
                    && substr((string) $rule->end_time, 0, 8) >= $endsAt->format('H:i:s');
            });
    }

    protected function periodsOverlap(CarbonInterface $firstStart, CarbonInterface $firstEnd, CarbonInterface $secondStart, CarbonInterface $secondEnd): bool
    {
        return $firstStart->lt($secondEnd) && $firstEnd->gt($secondStart);
    }
}
