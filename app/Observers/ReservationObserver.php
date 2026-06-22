<?php

namespace App\Observers;

use App\Enums\ReservationStatus;
use App\Mail\AdminReservationNotificationMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Court;
use App\Models\Reservation;
use App\Services\ReservationAvailabilityService;
use App\Services\ReservationScheduleService;
use App\Services\UserReservationStatsService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReservationObserver
{
    protected array $deletingParticipantIds = [];

    public function saving(Reservation $reservation): void
    {
        $priceErrors = [];

        foreach (['court_price', 'equipment_price', 'total_price'] as $priceField) {
            if ((float) $reservation->{$priceField} < 0) {
                $priceErrors[$priceField] = 'Iznos ne moze biti negativan.';
            }
        }

        if ($priceErrors !== []) {
            throw ValidationException::withMessages($priceErrors);
        }

        if ($reservation->status === ReservationStatus::Reserved) {
            $this->validateReservedSchedule($reservation);
        }

        if ($reservation->status === ReservationStatus::Cancelled) {
            $reservation->cancelled_at ??= now();
            $reservation->cancellation_reason = filled($reservation->cancellation_reason)
                ? $reservation->cancellation_reason
                : 'Otkazano od administratora.';

            return;
        }

        $reservation->cancelled_at = null;
        $reservation->cancellation_reason = null;
    }

    public function saved(Reservation $reservation): void
    {
        $reservation->loadMissing(['user', 'sport', 'court']);

        $cacheKeys = $this->availabilityCacheKeys($reservation);
        $reservationId = $reservation->getKey();
        $wasCreated = $reservation->wasRecentlyCreated && $reservation->status === ReservationStatus::Reserved;
        $wasCancelled = $reservation->wasChanged('status') && $reservation->status === ReservationStatus::Cancelled;

        if ($reservation->user_id) {
            $reservation->participants()->syncWithoutDetaching([$reservation->user_id]);
        }

        app(UserReservationStatsService::class)->recalculateMany($this->affectedUserIds($reservation));

        DB::afterCommit(function () use ($cacheKeys, $reservationId, $wasCreated, $wasCancelled): void {
            $this->clearAvailabilityCache($cacheKeys);

            if (! $wasCreated && ! $wasCancelled) {
                return;
            }

            $reservation = Reservation::query()
                ->with(['user', 'sport', 'court', 'equipmentItems.equipment'])
                ->find($reservationId);

            if (! $reservation) {
                return;
            }

            if ($wasCreated) {
                if ($reservation->customer_display_email) {
                    $this->sendReservationMail($reservation->customer_display_email, new ReservationConfirmedMail($reservation), $reservation);
                }

                $this->sendReservationMail(config('arena.contact.email'), new AdminReservationNotificationMail($reservation, 'created'), $reservation);
            }

            if ($wasCancelled) {
                if ($reservation->customer_display_email) {
                    $this->sendReservationMail($reservation->customer_display_email, new ReservationCancelledMail($reservation), $reservation);
                }

                $this->sendReservationMail(config('arena.contact.email'), new AdminReservationNotificationMail($reservation, 'cancelled'), $reservation);
            }
        });
    }

    public function deleting(Reservation $reservation): void
    {
        $this->deletingParticipantIds[$reservation->id] = $this->affectedUserIds($reservation)->all();
    }

    public function deleted(Reservation $reservation): void
    {
        $cacheKeys = $this->availabilityCacheKeys($reservation);

        DB::afterCommit(fn () => $this->clearAvailabilityCache($cacheKeys));

        app(UserReservationStatsService::class)->recalculateMany($this->deletingParticipantIds[$reservation->id] ?? [$reservation->user_id]);

        unset($this->deletingParticipantIds[$reservation->id]);
    }

    protected function affectedUserIds(Reservation $reservation): Collection
    {
        return collect([
            $reservation->user_id,
            $reservation->getOriginal('user_id'),
        ])
            ->merge($reservation->participants()->pluck('users.id'))
            ->filter()
            ->unique()
            ->values();
    }

    protected function sendReservationMail(?string $recipient, object $mail, Reservation $reservation): void
    {
        if (blank($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->queue($mail);
        } catch (Throwable $exception) {
            Log::error('Reservation email failed.', [
                'reservation_id' => $reservation->id,
                'recipient' => $recipient,
                'mail' => $mail::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function availabilityCacheKeys(Reservation $reservation): array
    {
        $sportIds = collect([
            $reservation->sport_id,
            $reservation->getOriginal('sport_id'),
        ])->filter()->unique();

        $dates = collect([
            $reservation->starts_at,
            $reservation->getOriginal('starts_at'),
        ])
            ->filter()
            ->map(fn ($date): CarbonInterface => $date instanceof CarbonInterface ? $date : Carbon::parse($date));

        return $sportIds
            ->crossJoin($dates)
            ->flatMap(function (array $pair): array {
                [$sportId, $date] = $pair;

                return collect([0, 1, 2])
                    ->flatMap(function (int $offset) use ($sportId, $date): array {
                        $cacheDate = $date->copy()->subDays($offset)->toDateString();

                        return [
                            'booking.availability.'.$sportId.'.'.$cacheDate,
                            'booking.availability.v2.'.$sportId.'.'.$cacheDate,
                        ];
                    })
                    ->all();
            })
            ->unique()
            ->values()
            ->all();
    }

    protected function clearAvailabilityCache(array $cacheKeys): void
    {
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    protected function validateReservedSchedule(Reservation $reservation): void
    {
        if (! $reservation->court_id || ! $reservation->sport_id || ! $reservation->starts_at || ! $reservation->ends_at) {
            return;
        }

        $court = Court::query()->find($reservation->court_id);

        if (! $court || (int) $court->sport_id !== (int) $reservation->sport_id) {
            throw ValidationException::withMessages([
                'court_id' => 'Izabrani teren ne pripada izabranom sportu.',
            ]);
        }

        if (! app(ReservationScheduleService::class)->isWithinOperatingHours($reservation->starts_at, $reservation->ends_at)) {
            throw ValidationException::withMessages([
                'starts_at' => 'Termin mora biti u okviru radnog vremena od 08:00 do 23:00.',
            ]);
        }

        $durationMinutes = (int) $reservation->starts_at->diffInMinutes($reservation->ends_at);

        if ((int) $reservation->duration_minutes !== $durationMinutes || ! in_array($durationMinutes, [60, 90, 120], true)) {
            throw ValidationException::withMessages([
                'duration_minutes' => 'Trajanje termina mora biti 60, 90 ili 120 minuta i odgovarati vremenu zavrsetka.',
            ]);
        }

        if (! app(ReservationAvailabilityService::class)->isAvailable(
            $court,
            $reservation->starts_at,
            $reservation->ends_at,
            $reservation->exists ? $reservation->getKey() : null,
        )) {
            throw ValidationException::withMessages([
                'starts_at' => 'Izabrani termin nije dostupan za ovaj teren.',
            ]);
        }
    }
}
