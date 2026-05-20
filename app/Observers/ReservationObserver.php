<?php

namespace App\Observers;

use App\Enums\ReservationStatus;
use App\Models\Reservation;

class ReservationObserver
{
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
