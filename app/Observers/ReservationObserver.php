<?php

namespace App\Observers;

use App\Enums\ReservationStatus;
use App\Mail\AdminReservationNotificationMail;
use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;

class ReservationObserver
{
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

        $user = $reservation->user;

        $user->forceFill([
            'total_reservations' => $user->reservations()->count(),
            'cancelled_reservations' => $user->reservations()
                ->where('status', ReservationStatus::Cancelled->value)
                ->count(),
            'last_reservation_at' => $user->reservations()->max('starts_at'),
        ])->saveQuietly();

        if ($reservation->wasRecentlyCreated && $reservation->status === ReservationStatus::Reserved) {
            Mail::to($reservation->user->email)->send(new ReservationConfirmedMail($reservation));
            Mail::to(config('arena.contact.email'))->send(new AdminReservationNotificationMail($reservation, 'created'));
        }

        if ($reservation->wasChanged('status') && $reservation->status === ReservationStatus::Cancelled) {
            Mail::to($reservation->user->email)->send(new ReservationCancelledMail($reservation));
            Mail::to(config('arena.contact.email'))->send(new AdminReservationNotificationMail($reservation, 'cancelled'));
        }
    }

    public function deleted(Reservation $reservation): void
    {
        $this->saved($reservation);
    }
}
