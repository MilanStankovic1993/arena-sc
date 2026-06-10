<?php

namespace App\Observers;

use App\Enums\ReservationStatus;
use App\Mail\AdminReservationNotificationMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Reservation;
use App\Services\UserReservationStatsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

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
        $reservation->participants()->syncWithoutDetaching([$reservation->user_id]);

        app(UserReservationStatsService::class)->recalculateMany($this->affectedUserIds($reservation));

        if ($reservation->wasRecentlyCreated && $reservation->status === ReservationStatus::Reserved) {
            Mail::to($reservation->user->email)->send(new ReservationConfirmedMail($reservation));
            Mail::to(config('arena.contact.email'))->send(new AdminReservationNotificationMail($reservation, 'created'));
        }

        if ($reservation->wasChanged('status') && $reservation->status === ReservationStatus::Cancelled) {
            Mail::to($reservation->user->email)->send(new ReservationCancelledMail($reservation));
            Mail::to(config('arena.contact.email'))->send(new AdminReservationNotificationMail($reservation, 'cancelled'));
        }
    }

    public function deleting(Reservation $reservation): void
    {
        $this->deletingParticipantIds[$reservation->id] = $this->affectedUserIds($reservation)->all();
    }

    public function deleted(Reservation $reservation): void
    {
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
}
