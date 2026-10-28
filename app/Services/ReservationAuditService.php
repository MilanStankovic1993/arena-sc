<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationAudit;
use Illuminate\Support\Facades\Auth;

class ReservationAuditService
{
    public function record(Reservation $reservation, string $event, array $old = [], array $new = []): ReservationAudit
    {
        $actor = Auth::user();
        $console = app()->runningInConsole() && ! request()->route();

        return $reservation->audits()->create([
            'actor_id' => $actor?->getKey(),
            'actor_name' => $actor?->name ?: ($console ? 'Sistem / konzola' : ($reservation->guest_name ?: 'Gost')),
            'actor_type' => $actor ? 'user' : ($console ? 'system' : 'guest'),
            'event' => $event, 'old_values' => $old, 'new_values' => $new,
        ]);
    }

    public function saved(Reservation $reservation): void
    {
        $changes = array_diff_key($reservation->getChanges(), array_flip(['updated_at', 'deleted_at']));
        if ($changes !== []) {
            $old = array_intersect_key($reservation->getRawOriginal(), $changes);
            $this->record($reservation, 'updated', $old, $changes);
        }
    }
}
