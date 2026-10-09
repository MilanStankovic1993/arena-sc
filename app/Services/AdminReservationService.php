<?php

namespace App\Services;

use App\Models\Court;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AdminReservationService
{
    public function times(?int $courtId, ?string $date, int $duration): array
    {
        $court = Court::query()->with('sport')->find($courtId);
        if (! $court || ! $date || ! $court->is_active || ! $court->sport?->is_active) {
            return [];
        }

        $options = [];
        foreach (app(ReservationScheduleService::class)->buildDailySchedule($court, Carbon::parse($date), $duration) as $slot) {
            if ($slot['starts_at']->lte(now()) || ! app(ReservationAvailabilityService::class)->isAvailable($court, $slot['starts_at'], $slot['ends_at'])) {
                continue;
            }
            try {
                app(ReservationPricingService::class)->calculateCourtPrice($court, $slot['starts_at'], $slot['ends_at']);
                $options[$slot['starts_at']->format('H:i')] = $slot['starts_at']->format('H:i').' – '.$slot['ends_at']->format('H:i');
            } catch (RuntimeException) {
                // A slot without a price cannot be offered.
            }
        }

        return $options;
    }

    public function create(array $data): Reservation
    {
        return DB::transaction(function () use ($data): Reservation {
            $court = Court::query()->with('sport')->lockForUpdate()->findOrFail($data['court_id']);
            $duration = (int) $data['duration_minutes'];
            $time = $data['booking_time'];
            if (! in_array($duration, [60, 90, 120], true) || ! array_key_exists($time, $this->times($court->id, $data['booking_date'], $duration))) {
                throw ValidationException::withMessages(['booking_time' => 'Termin vise nije dostupan. Izaberite drugi slobodan termin.']);
            }
            $start = Carbon::parse($data['booking_date'].' '.$time);
            $end = $start->copy()->addMinutes($duration);
            $pricing = app(ReservationPricingService::class);
            try {
                $courtPrice = $pricing->calculateCourtPrice($court, $start, $end);
                $equipment = $pricing->hydrateEquipmentPricing($data['equipment'] ?? [], $court->sport_id, $start, $end);
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages(['equipment' => $exception->getMessage()]);
            }
            if (isset($data['court_price_override']) && $data['court_price_override'] !== '') {
                $courtPrice = (float) $data['court_price_override'];
            }
            $equipmentPrice = $pricing->calculateEquipmentPrice($equipment);
            $registered = ($data['customer_type'] ?? 'guest') === 'user';
            $reservation = Reservation::query()->create([
                'court_id' => $court->id, 'sport_id' => $court->sport_id,
                'user_id' => $registered ? $data['user_id'] : null,
                'guest_name' => $registered ? null : $data['guest_name'],
                'guest_phone' => $registered ? null : $data['guest_phone'],
                'guest_email' => $registered ? null : ($data['guest_email'] ?? null),
                'status' => 'reserved', 'starts_at' => $start, 'ends_at' => $end,
                'duration_minutes' => $duration, 'court_price' => $courtPrice,
                'equipment_price' => $equipmentPrice, 'total_price' => $courtPrice + $equipmentPrice,
                'customer_note' => $data['customer_note'] ?? null,
                'admin_note' => $data['admin_note'] ?? null,
            ]);
            $reservation->equipmentItems()->createMany($equipment->all());

            return $reservation;
        });
    }
}
