<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\AdminAnalyticsService;
use App\Enums\ReservationStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsTopCustomersTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Ucinak korisnika';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 8;

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        $service = app(AdminAnalyticsService::class);
        $filters = $this->pageFilters ?? [];
        $resolved = $service->resolveFilters($filters);

        return $table
            ->query(
                User::query()
                    ->where('role', 'customer')
                    ->when($resolved['userId'], fn (Builder $query, int $userId) => $query->whereKey($userId))
                    ->withCount([
                        'participatedReservations as filtered_reservations_count' => fn (Builder $query) => $service->constrainUserPerformanceReservationQuery($query, $filters),
                        'participatedReservations as filtered_reserved_count' => fn (Builder $query) => $service
                            ->constrainUserPerformanceReservationQuery($query, $filters)
                            ->where('status', ReservationStatus::Reserved->value),
                        'participatedReservations as filtered_cancelled_count' => fn (Builder $query) => $service
                            ->constrainUserPerformanceReservationQuery($query, $filters)
                            ->where('status', ReservationStatus::Cancelled->value),
                    ])
                    ->withSum([
                        'participatedReservations as filtered_revenue' => fn (Builder $query) => $service
                            ->constrainUserPerformanceReservationQuery($query, $filters)
                            ->whereIn('status', $service->revenueStatuses()),
                    ], 'total_price')
                    ->withSum([
                        'participatedReservations as filtered_duration_minutes' => fn (Builder $query) => $service
                            ->constrainUserPerformanceReservationQuery($query, $filters)
                            ->whereIn('status', $service->revenueStatuses()),
                    ], 'duration_minutes')
                    ->whereHas('participatedReservations', fn (Builder $query) => $service->constrainUserPerformanceReservationQuery($query, $filters))
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Korisnik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
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
                    ->label('Vrednost termina')
                    ->money('RSD', divideBy: 1)
                    ->sortable(),
                TextColumn::make('filtered_average_value')
                    ->label('Prosek')
                    ->state(fn (User $record): float => ((int) $record->filtered_reservations_count) > 0
                        ? ((float) $record->filtered_revenue / (int) $record->filtered_reservations_count)
                        : 0)
                    ->money('RSD', divideBy: 1),
            ])
            ->defaultSort('filtered_reservations_count', 'desc')
            ->paginated([5, 10, 25]);
    }
}
