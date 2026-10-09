<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Mail\ReservationSeriesMail;
use App\Models\Court;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
        if (! empty($data['repeat_weekly'])) {
            return $this->createSeries($data);
        }

        return $this->createSingle($data);
    }

    private function createSingle(array $data, ?string $seriesId = null): Reservation
    {
        return DB::transaction(function () use ($data, $seriesId): Reservation {
            $court = Court::query()->with('sport')->lockForUpdate()->findOrFail($data['court_id']);
            $duration = (int) $data['duration_minutes'];
            $time = $data['booking_time'];
            $start = Carbon::parse($data['booking_date'].' '.$time);
            $end = $start->copy()->addMinutes($duration);
            if (! in_array($duration, [60, 90, 120], true) || ! $court->is_active || ! $court->sport?->is_active
                || $start->lte(now()) || ! app(ReservationScheduleService::class)->isWithinOperatingHours($start, $end)
                || ! app(ReservationAvailabilityService::class)->isAvailable($court, $start, $end)) {
                throw ValidationException::withMessages(['booking_time' => 'Termin vise nije dostupan. Izaberite drugi slobodan termin.']);
            }
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
                'series_id' => $seriesId,
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

    public function dates(array $data): Collection
    {
        Validator::make($data, [
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'repeat_until' => ['required', 'date_format:Y-m-d', 'after_or_equal:booking_date', 'before_or_equal:'.Carbon::parse($data['booking_date'])->addYear()->toDateString()],
        ])->validate();
        $dates = collect();
        $last = Carbon::parse($data['repeat_until'])->startOfDay();
        for ($day = Carbon::parse($data['booking_date'])->startOfDay(); $day->lte($last); $day->addWeek()) {
            $dates->push($day->toDateString());
        }

        return $dates;
    }

    public function preview(array $data): Collection
    {
        $court = Court::query()->with('sport')->find($data['court_id'] ?? null);
        if (! $court || empty($data['booking_date']) || empty($data['booking_time']) || empty($data['repeat_until'])) {
            return collect();
        }
        try {
            $dates = $this->dates($data);
        } catch (ValidationException) {
            return collect();
        }

        return $dates->map(function (string $date) use ($data, $court): array {
            $start = Carbon::parse($date.' '.$data['booking_time']);
            $end = $start->copy()->addMinutes((int) ($data['duration_minutes'] ?? 60));
            $reason = null;
            $price = 0.0;
            if ($start->lte(now()) || ! app(ReservationScheduleService::class)->isWithinOperatingHours($start, $end)) {
                $reason = 'Van radnog vremena ili u proslosti';
            } elseif (! $court->is_active || ! $court->sport?->is_active) {
                $reason = 'Teren nije aktivan';
            } elseif (! app(ReservationAvailabilityService::class)->isAvailable($court, $start, $end)) {
                $reason = 'Zauzeto / blokada terena';
            } else {
                try {
                    $pricing = app(ReservationPricingService::class);
                    $price = $pricing->calculateCourtPrice($court, $start, $end);
                    if (isset($data['court_price_override']) && $data['court_price_override'] !== '') {
                        $price = (float) $data['court_price_override'];
                    }
                    $equipment = $pricing->hydrateEquipmentPricing($data['equipment'] ?? [], $court->sport_id, $start, $end);
                    $price += $pricing->calculateEquipmentPrice($equipment);
                } catch (RuntimeException $exception) {
                    $reason = $exception->getMessage();
                }
            }

            return ['date' => $date, 'time' => $start->format('H:i').'–'.$end->format('H:i'), 'reason' => $reason, 'price' => $price];
        });
    }

    private function createSeries(array $data): Reservation
    {
        return DB::transaction(function () use ($data): Reservation {
            Court::query()->lockForUpdate()->findOrFail($data['court_id']);
            $dates = $this->dates($data)->diff($data['skip_dates'] ?? []);
            if ($dates->isEmpty()) {
                throw ValidationException::withMessages(['repeat_until' => 'Izaberite najmanje jedan termin.']);
            }
            $conflicts = $this->preview($data)->whereIn('date', $dates)->filter(fn ($row) => $row['reason'] !== null);
            if ($conflicts->isNotEmpty()) {
                throw ValidationException::withMessages(['skip_dates' => 'Nisu sacuvani termini. Preskocite zauzete datume ili promenite teren/vreme: '.$conflicts->pluck('date')->implode(', ')]);
            }
            $seriesId = (string) Str::uuid();
            $reservations = $dates->map(fn ($date) => $this->createSingle([...$data, 'booking_date' => $date], $seriesId));
            $this->notifySeries($reservations);

            return $reservations->first();
        });
    }

    public function cancelRemaining(Reservation $reservation): int
    {
        if (! $reservation->series_id) {
            return 0;
        }

        return DB::transaction(function () use ($reservation): int {
            Court::query()->lockForUpdate()->findOrFail($reservation->court_id);
            $records = Reservation::query()->where('series_id', $reservation->series_id)
                ->where('starts_at', '>=', now())->where('status', ReservationStatus::Reserved->value)
                ->orderBy('starts_at')->lockForUpdate()->get();
            foreach ($records as $record) {
                $record->suppressNotification = true;
                $record->update(['status' => ReservationStatus::Cancelled, 'cancellation_reason' => 'Otkazana preostala serija od administratora.']);
            }
            if ($records->isNotEmpty()) {
                $this->notifySeries($records, true);
            }

            return $records->count();
        });
    }

    private function notifySeries(Collection $reservations, bool $cancelled = false): void
    {
        DB::afterCommit(function () use ($reservations, $cancelled): void {
            $reservations->each(fn (Reservation $record) => $record->loadMissing(['court', 'sport', 'user']));
            $recipients = collect([$reservations->first()->customer_display_email, config('arena.contact.email')])->filter()->unique();
            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient)->queue(new ReservationSeriesMail($reservations, $cancelled));
                } catch (\Throwable $exception) {
                    Log::error('Reservation series email failed.', ['series_id' => $reservations->first()->series_id, 'error' => $exception->getMessage()]);
                }
            }
        });
    }
}
