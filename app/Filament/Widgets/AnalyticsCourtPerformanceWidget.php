<?php

namespace App\Filament\Widgets;

use App\Services\AdminAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class AnalyticsCourtPerformanceWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.analytics-court-performance-widget';

    protected int | string | array $columnSpan = [
        'xl' => 12,
    ];

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        return [
            'courts' => app(AdminAnalyticsService::class)->courtPerformance($this->pageFilters ?? []),
        ];
    }
}
