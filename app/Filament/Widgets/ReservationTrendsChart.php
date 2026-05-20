<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ReservationTrendsChart extends ChartWidget
{
    protected ?string $heading = 'Trend rezervacija po mesecima';

    protected function getData(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $offset) => Carbon::now()->startOfMonth()->subMonths($offset));

        return [
            'datasets' => [
                [
                    'label' => 'Broj rezervacija',
                    'data' => $months
                        ->map(fn (Carbon $month) => Reservation::query()
                            ->whereBetween('starts_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                            ->count())
                        ->all(),
                ],
            ],
            'labels' => $months->map(fn (Carbon $month) => $month->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
