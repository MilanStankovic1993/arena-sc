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
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.18)',
                    'pointBackgroundColor' => '#f59e0b',
                    'pointBorderColor' => '#fef3c7',
                    'pointRadius' => 4,
                    'pointHoverRadius' => 5,
                    'fill' => true,
                    'tension' => 0.35,
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
