<?php

namespace App\Filament\Widgets;

use App\Services\AdminAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class AnalyticsTimePopularityWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.analytics-time-popularity-widget';

    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 6,
    ];

    protected static ?int $sort = 6;

    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        return [
            'hours' => app(AdminAnalyticsService::class)->popularityByHour($this->pageFilters ?? []),
        ];
    }
}
