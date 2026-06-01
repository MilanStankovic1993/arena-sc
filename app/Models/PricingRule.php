<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PricingRule extends Model
{
    protected $fillable = [
        'sport_id',
        'name',
        'days_of_week',
        'start_time',
        'end_time',
        'price_60',
        'price_90',
        'price_120',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'price_60' => 'decimal:2',
            'price_90' => 'decimal:2',
            'price_120' => 'decimal:2',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $rule): void {
            $conflict = static::findConflictingRule($rule);

            if (! $conflict) {
                return;
            }

            throw ValidationException::withMessages([
                'start_time' => sprintf(
                    'Vec postoji definisano pravilo za ovaj period: %s (%s, %s - %s).',
                    $conflict->name,
                    $conflict->days_label,
                    substr((string) $conflict->start_time, 0, 5),
                    substr((string) $conflict->end_time, 0, 5),
                ),
            ]);
        });
    }

    public function getDaysLabelAttribute(): string
    {
        $days = Arr::wrap($this->days_of_week);

        if ($days === []) {
            return 'Svi dani';
        }

        $labels = [
            0 => 'Nedelja',
            1 => 'Ponedeljak',
            2 => 'Utorak',
            3 => 'Sreda',
            4 => 'Cetvrtak',
            5 => 'Petak',
            6 => 'Subota',
        ];

        return collect($days)
            ->map(fn (int|string $day): string => $labels[(int) $day] ?? (string) $day)
            ->implode(', ');
    }

    public function priceForDuration(int $durationMinutes): float
    {
        return match ($durationMinutes) {
            90 => (float) $this->price_90,
            120 => (float) $this->price_120,
            default => (float) $this->price_60,
        };
    }

    public static function findConflictingRule(self $rule): ?self
    {
        return static::query()
            ->where('sport_id', $rule->sport_id)
            ->when($rule->exists, fn ($query) => $query->whereKeyNot($rule->getKey()))
            ->whereTime('start_time', '<', $rule->end_time)
            ->whereTime('end_time', '>', $rule->start_time)
            ->get()
            ->first(function (self $existingRule) use ($rule): bool {
                return static::daysOverlap($existingRule->days_of_week, $rule->days_of_week)
                    && static::dateRangesOverlap(
                        $existingRule->valid_from,
                        $existingRule->valid_to,
                        $rule->valid_from,
                        $rule->valid_to,
                    );
            });
    }

    protected static function daysOverlap(null|array $existingDays, null|array $incomingDays): bool
    {
        $existingDays = array_values(array_map('intval', Arr::wrap($existingDays)));
        $incomingDays = array_values(array_map('intval', Arr::wrap($incomingDays)));

        if (($existingDays === []) || ($incomingDays === [])) {
            return true;
        }

        return count(array_intersect($existingDays, $incomingDays)) > 0;
    }

    protected static function dateRangesOverlap(mixed $existingFrom, mixed $existingTo, mixed $incomingFrom, mixed $incomingTo): bool
    {
        $existingFrom = $existingFrom ? Carbon::parse($existingFrom)->startOfDay() : Carbon::create(1970, 1, 1)->startOfDay();
        $existingTo = $existingTo ? Carbon::parse($existingTo)->endOfDay() : Carbon::create(2999, 12, 31)->endOfDay();
        $incomingFrom = $incomingFrom ? Carbon::parse($incomingFrom)->startOfDay() : Carbon::create(1970, 1, 1)->startOfDay();
        $incomingTo = $incomingTo ? Carbon::parse($incomingTo)->endOfDay() : Carbon::create(2999, 12, 31)->endOfDay();

        return $existingFrom->lte($incomingTo) && $incomingFrom->lte($existingTo);
    }
}
