<?php

namespace App\Services;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\PricingRule;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ReservationPricingService
{
    public function calculateCourtPrice(Court $court, CarbonInterface $startsAt, CarbonInterface $endsAt): float
    {
        $rule = PricingRule::query()
            ->where('court_id', $court->id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('day_of_week')->orWhere('day_of_week', (int) $startsAt->dayOfWeek))
            ->whereTime('start_time', '<=', $startsAt->format('H:i:s'))
            ->whereTime('end_time', '>=', $endsAt->format('H:i:s'))
            ->where(fn ($query) => $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $startsAt->toDateString()))
            ->where(fn ($query) => $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $startsAt->toDateString()))
            ->orderByDesc('price')
            ->first();

        if ($rule) {
            return (float) $rule->price;
        }

        $hours = max(1, (int) ceil($startsAt->diffInMinutes($endsAt) / 60));

        return (float) $court->base_price * $hours;
    }

    public function hydrateEquipmentPricing(array $equipmentSelections): Collection
    {
        return collect($equipmentSelections)
            ->filter(fn (array $item): bool => (int) ($item['quantity'] ?? 0) > 0)
            ->map(function (array $item): array {
                $equipment = Equipment::query()->findOrFail($item['equipment_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $equipment->rental_price;

                return [
                    'equipment_id' => $equipment->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $quantity,
                ];
            })
            ->values();
    }

    public function calculateEquipmentPrice(Collection $items): float
    {
        return (float) $items->sum(fn (array $item): float => (float) $item['line_total']);
    }
}
