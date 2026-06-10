<?php

namespace App\Filament\Widgets;

use App\Services\AdminAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class AnalyticsDurationDistributionWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.analytics-duration-distribution-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 7;

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        return [
            'durations' => app(AdminAnalyticsService::class)->durationDistribution($this->pageFilters ?? []),
        ];
    }
}
