<?php

namespace App\Filament\Widgets;

use App\Services\AdminAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AnalyticsRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Mesecni prihod - poslednjih 6 meseci';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected function getData(): array
    {
        $series = app(AdminAnalyticsService::class)->monthlyRevenue($this->pageFilters ?? []);

        return [
            'datasets' => [
                [
                    'label' => 'Prihod',
                    'borderWidth' => 0,
                    'backgroundColor' => '#3b82f6',
                    'borderRadius' => 10,
                    'maxBarThickness' => 44,
                    'data' => $series['data'],
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => null,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
