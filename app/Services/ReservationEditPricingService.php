<?php

namespace App\Services;

use App\Models\Court;
use App\Models\Reservation;
use Carbon\Carbon;
use RuntimeException;

class ReservationEditPricingService
{
    public function synchronizeTimes(array $data, string $changed): array
    {
        if (blank($data['starts_at'] ?? null)) {
            return $data;
        }
        $start = $this->date($data['starts_at']);
        if (in_array($changed, ['starts_at', 'duration_minutes'], true)) {
            $duration = (int) ($data['duration_minutes'] ?? 0);
            if (in_array($duration, [60, 90, 120], true)) {
                $data['ends_at'] = $start->copy()->addMinutes($duration)->toDateTimeString();
            }
        } elseif ($changed === 'ends_at' && filled($data['ends_at'] ?? null)) {
            $minutes = $start->diffInMinutes($this->date($data['ends_at']), false);
            if (! in_array($minutes, [60, 90, 120], false)) {
                throw new RuntimeException('Termin mora trajati 60, 90 ili 120 minuta.');
            }
            $data['duration_minutes'] = (int) $minutes;
        }

        return $data;
    }

    public function courtPrice(array $data): float
    {
        $court = Court::find($data['court_id'] ?? null);
        if (! $court || (int) $court->sport_id !== (int) ($data['sport_id'] ?? 0)) {
            throw new RuntimeException('Izaberite teren za izabrani sport.');
        }
        if (blank($data['starts_at'] ?? null) || blank($data['ends_at'] ?? null)) {
            throw new RuntimeException('Unesite početak i kraj termina.');
        }
        $start = $this->date($data['starts_at']);
        $end = $this->date($data['ends_at']);
        $minutes = $start->diffInMinutes($end, false);
        if (! in_array($minutes, [60, 90, 120], false) || (int) ($data['duration_minutes'] ?? 0) !== (int) $minutes) {
            throw new RuntimeException('Trajanje i vreme završetka moraju odgovarati terminu od 60, 90 ili 120 minuta.');
        }
        if (! app(ReservationScheduleService::class)->isWithinOperatingHours($start, $end)) {
            throw new RuntimeException('Termin mora biti u okviru radnog vremena od 08:00 do ponoći.');
        }

        $openingPromotion = $start->toDateString() >= '2026-10-16' && $start->toDateString() <= '2026-10-18';

        return ! empty($data['is_gift']) || $openingPromotion ? 0 : app(ReservationPricingService::class)->calculateCourtPrice($court, $start, $end);
    }

    public function prepareForSave(array $data, Reservation $record): array
    {
        $startChanged = $this->dateChanged($data['starts_at'] ?? null, $record->starts_at);
        $endChanged = $this->dateChanged($data['ends_at'] ?? null, $record->ends_at);
        $durationChanged = (int) ($data['duration_minutes'] ?? 0) !== (int) $record->duration_minutes;
        $contextChanged = $startChanged || $endChanged || $durationChanged
            || (int) ($data['court_id'] ?? 0) !== (int) $record->court_id
            || (int) ($data['sport_id'] ?? 0) !== (int) $record->sport_id;
        if (($durationChanged || $startChanged) && ! $endChanged) {
            $data = $this->synchronizeTimes($data, 'duration_minutes');
        } elseif ($endChanged && ! $durationChanged) {
            $data = $this->synchronizeTimes($data, 'ends_at');
        }
        $giftChanged = (bool) ($data['is_gift'] ?? false) !== (bool) $record->is_gift;
        if ($contextChanged || $giftChanged) {
            $price = $this->courtPrice($data);
            if (empty($data['court_price_manually_changed']) || $giftChanged) {
                $data['court_price'] = $price;
            }
        }
        if (! empty($data['is_gift'])) {
            $data['court_price'] = $data['equipment_price'] = $data['total_price'] = 0;
        } elseif ($contextChanged || $giftChanged
            || (float) ($data['court_price'] ?? 0) !== (float) $record->court_price
            || (float) ($data['equipment_price'] ?? 0) !== (float) $record->equipment_price) {
            $data['total_price'] = round((float) ($data['court_price'] ?? 0) + (float) ($data['equipment_price'] ?? 0), 2);
        }
        unset($data['court_price_manually_changed'], $data['pricing_error']);

        return $data;
    }

    private function date(mixed $value): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\InvalidArgumentException) {
            throw new RuntimeException('Unesite ispravan datum i vreme termina.');
        }
    }

    private function dateChanged(mixed $value, Carbon $original): bool
    {
        return blank($value) || ! $this->date($value)->equalTo($original);
    }
}
