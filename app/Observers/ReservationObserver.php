<?php

namespace App\Observers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;

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
        $user = $reservation->user;

        $user->forceFill([
            'total_reservations' => $user->reservations()->count(),
            'cancelled_reservations' => $user->reservations()
                ->where('status', ReservationStatus::Cancelled->value)
                ->count(),
            'last_reservation_at' => $user->reservations()->max('starts_at'),
        ])->saveQuietly();
    }

    public function deleted(Reservation $reservation): void
    {
        $this->saved($reservation);
    }
}
