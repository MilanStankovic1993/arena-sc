<?php

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Models\Court;
use App\Services\AdminAnalyticsService;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsCourtPerformanceTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Tabela ucinka terena';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        $service = app(AdminAnalyticsService::class);
        $filters = $this->pageFilters ?? [];
        $resolved = $service->resolveFilters($filters);

        return $table
            ->query(
                Court::query()
                    ->with('sport')
                    ->when($resolved['sportId'], fn (Builder $query, int $sportId) => $query->where('sport_id', $sportId))
                    ->when($resolved['courtId'], fn (Builder $query, int $courtId) => $query->whereKey($courtId))
                    ->withCount([
                        'reservations as filtered_reservations_count' => fn (Builder $query) => $service->constrainReservationQuery($query, $filters),
                        'reservations as filtered_reserved_count' => fn (Builder $query) => $service
                            ->constrainReservationQuery($query, $filters)
                            ->where('status', ReservationStatus::Reserved->value),
                        'reservations as filtered_cancelled_count' => fn (Builder $query) => $service
                            ->constrainReservationQuery($query, $filters)
                            ->where('status', ReservationStatus::Cancelled->value),
                    ])
                    ->withSum([
                        'reservations as filtered_revenue' => fn (Builder $query) => $service
                            ->constrainReservationQuery($query, $filters)
                            ->whereIn('status', $service->revenueStatuses()),
                    ], 'total_price')
                    ->withSum([
                        'reservations as filtered_duration_minutes' => fn (Builder $query) => $service
                            ->constrainReservationQuery($query, $filters)
                            ->whereIn('status', $service->revenueStatuses()),
                    ], 'duration_minutes')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Teren')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sport.name')
                    ->label('Sport')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktivan')
                    ->boolean(),
                TextColumn::make('filtered_reservations_count')
                    ->label('Ukupno')
                    ->sortable(),
                TextColumn::make('filtered_reserved_count')
                    ->label('Rezervisani')
                    ->sortable(),
                TextColumn::make('filtered_cancelled_count')
                    ->label('Otkazani')
                    ->sortable(),
                TextColumn::make('filtered_duration_minutes')
                    ->label('Sati')
                    ->formatStateUsing(fn (int|string|null $state): string => number_format(((int) $state) / 60, 1, ',', '.') . ' h')
                    ->sortable(),
                TextColumn::make('filtered_revenue')
                    ->label('Prihod')
                    ->money('RSD', divideBy: 1)
                    ->sortable(),
                TextColumn::make('filtered_average_value')
                    ->label('Prosek')
                    ->state(fn (Court $record): float => ((int) $record->filtered_reservations_count) > 0
                        ? ((float) $record->filtered_revenue / (int) $record->filtered_reservations_count)
                        : 0)
                    ->money('RSD', divideBy: 1),
            ])
            ->defaultSort('filtered_reservations_count', 'desc')
            ->paginated([5, 10, 25]);
    }
}
