<?php

namespace App\Observers;

use App\Enums\ReservationStatus;
use App\Mail\AdminReservationNotificationMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Reservation;
use App\Services\UserReservationStatsService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ReservationObserver
{
    protected array $deletingParticipantIds = [];

    public function saving(Reservation $reservation): void
    {
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

        $this->clearAvailabilityCache($reservation);

        if ($reservation->user_id) {
            $reservation->participants()->syncWithoutDetaching([$reservation->user_id]);
        }

        app(UserReservationStatsService::class)->recalculateMany($this->affectedUserIds($reservation));

        if ($reservation->wasRecentlyCreated && $reservation->status === ReservationStatus::Reserved) {
            if ($reservation->customer_display_email) {
                $this->sendReservationMail($reservation->customer_display_email, new ReservationConfirmedMail($reservation), $reservation);
            }

            $this->sendReservationMail(config('arena.contact.email'), new AdminReservationNotificationMail($reservation, 'created'), $reservation);
        }

        if ($reservation->wasChanged('status') && $reservation->status === ReservationStatus::Cancelled) {
            if ($reservation->customer_display_email) {
                $this->sendReservationMail($reservation->customer_display_email, new ReservationCancelledMail($reservation), $reservation);
            }

            $this->sendReservationMail(config('arena.contact.email'), new AdminReservationNotificationMail($reservation, 'cancelled'), $reservation);
        }
    }

    public function deleting(Reservation $reservation): void
    {
        $this->deletingParticipantIds[$reservation->id] = $this->affectedUserIds($reservation)->all();
    }

    public function deleted(Reservation $reservation): void
    {
        $this->clearAvailabilityCache($reservation);

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
            Mail::to($recipient)->send($mail);
        } catch (Throwable $exception) {
            Log::error('Reservation email failed.', [
                'reservation_id' => $reservation->id,
                'recipient' => $recipient,
                'mail' => $mail::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function clearAvailabilityCache(Reservation $reservation): void
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

        foreach ($sportIds as $sportId) {
            foreach ($dates as $date) {
                foreach ([0, 1, 2] as $offset) {
                    $cacheDate = $date->copy()->subDays($offset)->toDateString();

                    Cache::forget('booking.availability.' . $sportId . '.' . $cacheDate);
                    Cache::forget('booking.availability.v2.' . $sportId . '.' . $cacheDate);
                }
            }
        }
    }
}
