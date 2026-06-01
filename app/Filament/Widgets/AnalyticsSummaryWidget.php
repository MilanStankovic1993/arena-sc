<?php

namespace App\Filament\Widgets;

use App\Services\AdminAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class AnalyticsSummaryWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.analytics-summary-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        $service = app(AdminAnalyticsService::class);
        $filters = $this->pageFilters ?? [];

        return [
            'filterSummary' => $service->getFilterSummary($filters),
            'today' => $service->todaySnapshot($filters),
            'period' => $service->periodSnapshot($filters),
            'health' => $service->systemHealthSnapshot($filters),
        ];
    }
}
