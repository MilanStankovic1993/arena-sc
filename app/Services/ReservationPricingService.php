<?php

namespace App\Services;

use App\Models\Court;
use App\Models\Equipment;
use App\Models\PricingRule;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class ReservationPricingService
{
    public function calculateCourtPrice(Court $court, CarbonInterface $startsAt, CarbonInterface $endsAt): float
    {
        $pricingRules = PricingRule::query()
            ->where('sport_id', $court->sport_id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('valid_from')->orWhereDate('valid_from', '<=', $startsAt->toDateString()))
            ->where(fn ($query) => $query->whereNull('valid_to')->orWhereDate('valid_to', '>=', $startsAt->toDateString()))
            ->orderBy('start_time')
            ->get();

        return $this->calculateCourtPriceFromRules($court, $pricingRules, $startsAt, $endsAt);
    }

    public function calculateCourtPriceFromRules(Court $court, Collection $pricingRules, CarbonInterface $startsAt, CarbonInterface $endsAt): float
    {
        $durationMinutes = (int) $startsAt->diffInMinutes($endsAt);

        $singleRule = $pricingRules->first(
            fn (PricingRule $rule): bool => $this->ruleCoversPeriod($rule, $court, $startsAt, $endsAt)
        );

        if ($singleRule) {
            return $singleRule->priceForDuration($durationMinutes);
        }

        $cursor = $startsAt->copy();
        $total = 0.0;

        while ($cursor->lt($endsAt)) {
            $rule = $pricingRules->first(
                fn (PricingRule $rule): bool => $this->ruleCoversMoment($rule, $court, $cursor)
            );

            if (! $rule) {
                throw new RuntimeException('Cenovnik termina nije definisan za izabrani sport, dan, vreme i trajanje.');
            }

            $ruleEnd = $cursor->copy()->setTimeFromTimeString(substr((string) $rule->end_time, 0, 8));

            if ($ruleEnd->lte($cursor)) {
                throw new RuntimeException('Cenovnik termina nije definisan za izabrani sport, dan, vreme i trajanje.');
            }

            $segmentEnd = $ruleEnd->lt($endsAt) ? $ruleEnd : $endsAt->copy();
            $segmentMinutes = (int) $cursor->diffInMinutes($segmentEnd);

            $total += ((float) $rule->price_60) * ($segmentMinutes / 60);
            $cursor = $segmentEnd->copy();
        }

        if ($total > 0) {
            return round($total, 2);
        }

        throw new RuntimeException('Cenovnik termina nije definisan za izabrani sport, dan, vreme i trajanje.');
    }

    protected function ruleCoversPeriod(PricingRule $rule, Court $court, CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        return $this->ruleAppliesToDate($rule, $court, $startsAt)
            && substr((string) $rule->start_time, 0, 8) <= $startsAt->format('H:i:s')
            && substr((string) $rule->end_time, 0, 8) >= $endsAt->format('H:i:s');
    }

    protected function ruleCoversMoment(PricingRule $rule, Court $court, CarbonInterface $startsAt): bool
    {
        return $this->ruleAppliesToDate($rule, $court, $startsAt)
            && substr((string) $rule->start_time, 0, 8) <= $startsAt->format('H:i:s')
            && substr((string) $rule->end_time, 0, 8) > $startsAt->format('H:i:s');
    }

    protected function ruleAppliesToDate(PricingRule $rule, Court $court, CarbonInterface $startsAt): bool
    {
        if ((int) $rule->sport_id !== (int) $court->sport_id) {
            return false;
        }

        $days = collect($rule->days_of_week ?? [])->map(fn ($day): int => (int) $day);

        if ($days->isNotEmpty() && ! $days->contains((int) $startsAt->dayOfWeek)) {
            return false;
        }

        if ($rule->valid_from && $startsAt->toDateString() < $rule->valid_from->toDateString()) {
            return false;
        }

        if ($rule->valid_to && $startsAt->toDateString() > $rule->valid_to->toDateString()) {
            return false;
        }

        return true;
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
