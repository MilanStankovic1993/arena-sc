<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AnalyticsCourtPerformanceTable;
use App\Filament\Widgets\AnalyticsCourtPerformanceWidget;
use App\Filament\Widgets\AnalyticsDurationDistributionWidget;
use App\Filament\Widgets\AnalyticsRevenueChart;
use App\Filament\Widgets\AnalyticsSummaryWidget;
use App\Filament\Widgets\AnalyticsTimePopularityWidget;
use App\Filament\Widgets\AnalyticsTopCustomersTable;
use App\Filament\Widgets\AnalyticsWeekdayPopularityWidget;
use App\Models\Court;
use App\Models\Sport;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Statistics extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Statistika';

    protected static ?string $navigationLabel = 'Statistika';

    protected static string|\UnitEnum|null $navigationGroup = 'Korisnici i analitika';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 2;

    protected static string $routePath = 'statistika';

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 12,
        ];
    }

    public function getWidgets(): array
    {
        return [
            AnalyticsSummaryWidget::class,
            AnalyticsRevenueChart::class,
            AnalyticsCourtPerformanceWidget::class,
            AnalyticsCourtPerformanceTable::class,
            AnalyticsWeekdayPopularityWidget::class,
            AnalyticsTimePopularityWidget::class,
            AnalyticsDurationDistributionWidget::class,
            AnalyticsTopCustomersTable::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter statistike')
                    ->description('Pregledaj metrike po periodu, sportu, terenu i konkretnom korisniku.')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('preset')
                            ->label('Period')
                            ->options([
                                '7d' => '7 dana',
                                '30d' => '30 dana',
                                '90d' => '90 dana',
                                '365d' => '365 dana',
                                'year' => 'Tekuca godina',
                                'all' => 'Sve vreme',
                                'custom' => 'Prilagodi',
                            ])
                            ->default('30d')
                            ->live(),
                        DatePicker::make('startDate')
                            ->label('Od datuma')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->visible(fn ($get): bool => ($get('preset') ?? '30d') === 'custom'),
                        DatePicker::make('endDate')
                            ->label('Do datuma')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->visible(fn ($get): bool => ($get('preset') ?? '30d') === 'custom'),
                        Select::make('sportId')
                            ->label('Sport')
                            ->placeholder('Svi sportovi')
                            ->options(fn (): array => Sport::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->live(),
                        Select::make('courtId')
                            ->label('Teren')
                            ->placeholder('Svi tereni')
                            ->options(function ($get): array {
                                return Court::query()
                                    ->when($get('sportId'), fn ($query, $sportId) => $query->where('sport_id', $sportId))
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->searchable(),
                        Select::make('userId')
                            ->label('Korisnik')
                            ->placeholder('Svi korisnici')
                            ->options(fn (): array => User::query()
                                ->where('role', 'customer')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable(),
                    ])
                    ->columns([
                        'md' => 2,
                        'xl' => 6,
                    ]),
            ]);
    }
}
