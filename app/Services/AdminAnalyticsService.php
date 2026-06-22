<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsService
{
    public function resolveFilters(array $filters = []): array
    {
        $preset = $filters['preset'] ?? '30d';
        $now = now();

        [$startDate, $endDate] = match ($preset) {
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            '90d' => [$now->copy()->subDays(89)->startOfDay(), $now->copy()->endOfDay()],
            '365d' => [$now->copy()->subDays(364)->startOfDay(), $now->copy()->endOfDay()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'all' => [Carbon::create(2020, 1, 1)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                filled($filters['startDate'] ?? null)
                    ? Carbon::parse($filters['startDate'])->startOfDay()
                    : $now->copy()->subDays(29)->startOfDay(),
                filled($filters['endDate'] ?? null)
                    ? Carbon::parse($filters['endDate'])->endOfDay()
                    : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
        };

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [
            'preset' => $preset,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sportId' => filled($filters['sportId'] ?? null) ? (int) $filters['sportId'] : null,
            'courtId' => filled($filters['courtId'] ?? null) ? (int) $filters['courtId'] : null,
            'userId' => filled($filters['userId'] ?? null) ? (int) $filters['userId'] : null,
        ];
    }

    public function getFilterSummary(array $filters = []): string
    {
        $resolved = $this->resolveFilters($filters);

        return $resolved['startDate']->format('d.m.Y').' - '.$resolved['endDate']->format('d.m.Y');
    }

    public function selectedUserLabel(array $filters = []): ?string
    {
        $resolved = $this->resolveFilters($filters);

        if (! $resolved['userId']) {
            return null;
        }

        return User::query()->whereKey($resolved['userId'])->value('name');
    }

    public function todaySnapshot(array $filters = []): array
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();
        $query = $this->reservationQueryForRange($filters, $start, $end);

        return [
            'reservations' => (clone $query)->count(),
            'revenue' => (clone $query)->whereIn('status', $this->revenueStatuses())->sum('total_price'),
            'activeCourts' => $this->courtsQuery($filters)->where('is_active', true)->count(),
            'customers' => User::query()->where('role', 'customer')->count(),
        ];
    }

    public function periodSnapshot(array $filters = []): array
    {
        $query = $this->reservationQuery($filters);

        $total = (clone $query)->count();
        $reserved = (clone $query)
            ->where('status', ReservationStatus::Reserved->value)
            ->count();
        $cancelled = (clone $query)
            ->whereIn('status', $this->cancelledStatuses())
            ->count();
        $revenue = (clone $query)
            ->whereIn('status', $this->revenueStatuses())
            ->sum('total_price');
        $participantQuery = $this->participantQueryForReservations($query, $filters);
        $participantVisits = (clone $participantQuery)->count();
        $uniqueParticipants = (clone $participantQuery)->distinct('user_id')->count('user_id');

        return [
            'total' => $total,
            'reserved' => $reserved,
            'cancelled' => $cancelled,
            'revenue' => $revenue,
            'averagePrice' => $total > 0 ? $revenue / $total : 0,
            'participantVisits' => $participantVisits,
            'uniqueParticipants' => $uniqueParticipants,
        ];
    }

    public function systemHealthSnapshot(array $filters = []): array
    {
        $resolved = $this->resolveFilters($filters);
        $query = $this->reservationQuery($filters);
        $total = (clone $query)->count();
        $cancelled = (clone $query)->whereIn('status', $this->cancelledStatuses())->count();
        $nonCancelled = max($total - $cancelled, 0);

        $newUsers = User::query()
            ->where('role', 'customer')
            ->where(function (Builder $builder) use ($resolved): void {
                $builder
                    ->whereBetween('created_at', [$resolved['startDate'], $resolved['endDate']])
                    ->orWhere(function (Builder $nested) use ($resolved): void {
                        $nested
                            ->whereNotNull('registered_at')
                            ->whereBetween('registered_at', [
                                $resolved['startDate']->toDateString(),
                                $resolved['endDate']->toDateString(),
                            ]);
                    });
            })
            ->count();

        return [
            'cancellationRate' => $total > 0 ? ($cancelled / $total) * 100 : 0,
            'retentionRate' => $total > 0 ? ($nonCancelled / $total) * 100 : 0,
            'newUsers' => $newUsers,
            'totalUsers' => User::query()->where('role', 'customer')->count(),
        ];
    }

    public function monthlyRevenue(array $filters = []): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $offset) => now()->copy()->startOfMonth()->subMonths($offset));

        $data = $months
            ->map(function (CarbonInterface $month) use ($filters): float {
                return (float) $this->reservationQueryForRange(
                    $filters,
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                )
                    ->whereIn('status', $this->revenueStatuses())
                    ->sum('total_price');
            })
            ->all();

        return [
            'labels' => $months->map(fn (CarbonInterface $month) => $month->translatedFormat('M'))->all(),
            'data' => $data,
        ];
    }

    public function courtPerformance(array $filters = []): Collection
    {
        $courts = $this->courtsQuery($filters)
            ->with('sport')
            ->orderBy('name')
            ->get();

        $statistics = $this->reservationQuery($filters)
            ->select('court_id')
            ->selectRaw('COUNT(*) as reservations_count')
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN total_price ELSE 0 END) as revenue',
                [ReservationStatus::Reserved->value],
            )
            ->groupBy('court_id')
            ->get()
            ->keyBy('court_id');

        $maxCount = max((int) $statistics->max('reservations_count'), 1);

        return $courts->map(function (Court $court) use ($statistics, $maxCount): array {
            $courtStatistics = $statistics->get($court->id);
            $count = (int) ($courtStatistics?->reservations_count ?? 0);
            $revenue = (float) ($courtStatistics?->revenue ?? 0);

            return [
                'name' => $court->name,
                'sport' => $court->sport?->name,
                'is_active' => (bool) $court->is_active,
                'reservations' => $count,
                'revenue' => $revenue,
                'progress' => min(100, round(($count / $maxCount) * 100, 1)),
            ];
        });
    }

    public function popularityByWeekday(array $filters = []): Collection
    {
        $labels = [
            1 => 'Pon',
            2 => 'Uto',
            3 => 'Sre',
            4 => 'Cet',
            5 => 'Pet',
            6 => 'Sub',
            7 => 'Ned',
        ];

        $weekdayExpression = DB::getDriverName() === 'sqlite'
            ? "CASE strftime('%w', starts_at) WHEN '0' THEN 7 ELSE CAST(strftime('%w', starts_at) AS INTEGER) END"
            : 'WEEKDAY(starts_at) + 1';

        $counts = $this->reservationQuery($filters)
            ->where('status', '!=', ReservationStatus::Cancelled->value)
            ->selectRaw("{$weekdayExpression} as bucket, COUNT(*) as aggregate")
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket')
            ->map(fn ($count): int => (int) $count);

        $maxCount = max((int) $counts->max(), 1);

        return collect($labels)->map(function (string $label, int $day) use ($counts, $maxCount): array {
            $count = (int) ($counts[$day] ?? 0);

            return [
                'label' => $label,
                'count' => $count,
                'progress' => min(100, round(($count / $maxCount) * 100, 1)),
            ];
        })->values();
    }

    public function popularityByHour(array $filters = []): Collection
    {
        $hourExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%H:00', starts_at)"
            : "DATE_FORMAT(starts_at, '%H:00')";

        $counts = $this->reservationQuery($filters)
            ->where('status', '!=', ReservationStatus::Cancelled->value)
            ->selectRaw("{$hourExpression} as bucket, COUNT(*) as aggregate")
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket')
            ->map(fn ($count): int => (int) $count)
            ->sortDesc()
            ->take(6);

        $maxCount = max((int) $counts->max(), 1);

        return $counts->map(function (int $count, string $hour) use ($maxCount): array {
            return [
                'label' => $hour,
                'count' => $count,
                'progress' => min(100, round(($count / $maxCount) * 100, 1)),
            ];
        })->values();
    }

    public function durationDistribution(array $filters = []): Collection
    {
        $counts = $this->reservationQuery($filters)
            ->where('status', '!=', ReservationStatus::Cancelled->value)
            ->selectRaw('duration_minutes, COUNT(*) as aggregate')
            ->groupBy('duration_minutes')
            ->pluck('aggregate', 'duration_minutes')
            ->map(fn ($count): int => (int) $count)
            ->sortKeys();

        $total = max((int) $counts->sum(), 1);

        return $counts->map(function (int $count, int|string $minutes) use ($total): array {
            $duration = (int) $minutes;

            return [
                'label' => $this->formatDurationLabel($duration),
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 1),
            ];
        })->values();
    }

    protected function formatDurationLabel(int $minutes): string
    {
        return match ($minutes) {
            60 => '1h',
            90 => '1,5h',
            120 => '2h',
            default => rtrim(rtrim(number_format($minutes / 60, 2, ',', ''), '0'), ',').'h',
        };
    }

    public function reservationQuery(array $filters = []): Builder
    {
        $resolved = $this->resolveFilters($filters);

        return $this->reservationQueryForRange($filters, $resolved['startDate'], $resolved['endDate']);
    }

    public function reservationQueryForRange(array $filters, CarbonInterface $startDate, CarbonInterface $endDate): Builder
    {
        return $this->constrainReservationQuery(
            Reservation::query(),
            $filters,
            $startDate,
            $endDate,
        );
    }

    public function courtsQuery(array $filters = []): Builder
    {
        $resolved = $this->resolveFilters($filters);

        return Court::query()
            ->when($resolved['sportId'], fn (Builder $query, int $sportId) => $query->where('sport_id', $sportId))
            ->when($resolved['courtId'], fn (Builder $query, int $courtId) => $query->whereKey($courtId));
    }

    public function constrainReservationQuery(
        Builder $query,
        array $filters = [],
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
        bool $applyUserFilter = true,
    ): Builder {
        $resolved = $this->resolveFilters($filters);
        $startDate ??= $resolved['startDate'];
        $endDate ??= $resolved['endDate'];

        return $query
            ->when($resolved['sportId'], fn (Builder $builder, int $sportId) => $builder->where('sport_id', $sportId))
            ->when($resolved['courtId'], fn (Builder $builder, int $courtId) => $builder->where('court_id', $courtId))
            ->when($applyUserFilter && $resolved['userId'], fn (Builder $builder) => $builder->whereHas('participants', fn (Builder $participants) => $participants->whereKey($resolved['userId'])))
            ->whereBetween('starts_at', [$startDate, $endDate]);
    }

    public function constrainUserPerformanceReservationQuery(Builder $query, array $filters = []): Builder
    {
        return $this->constrainReservationQuery($query, $filters, applyUserFilter: false);
    }

    protected function participantQueryForReservations(Builder $reservationQuery, array $filters = []): QueryBuilder
    {
        $resolved = $this->resolveFilters($filters);
        $reservationIds = (clone $reservationQuery)->select('reservations.id');

        return DB::table('reservation_participants')
            ->whereIn('reservation_id', $reservationIds)
            ->when($resolved['userId'], fn (QueryBuilder $query, int $userId) => $query->where('user_id', $userId));
    }

    public function revenueStatuses(): array
    {
        return [
            ReservationStatus::Reserved->value,
        ];
    }

    public function cancelledStatuses(): array
    {
        return [
            ReservationStatus::Cancelled->value,
        ];
    }
}
