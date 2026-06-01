<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\AdminAnalyticsService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AnalyticsTopCustomersTable extends TableWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Top korisnici';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 7;

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        $service = app(AdminAnalyticsService::class);
        $filters = $this->pageFilters ?? [];

        return $table
            ->query(
                User::query()
                    ->where('role', 'customer')
                    ->withCount([
                        'reservations as filtered_reservations_count' => fn (Builder $query) => $service->constrainReservationQuery($query, $filters),
                    ])
                    ->withSum([
                        'reservations as filtered_revenue' => fn (Builder $query) => $service
                            ->constrainReservationQuery($query, $filters)
                            ->whereIn('status', $service->revenueStatuses()),
                    ], 'total_price')
                    ->whereHas('reservations', fn (Builder $query) => $service->constrainReservationQuery($query, $filters))
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
                    ->label('Termina')
                    ->sortable(),
                TextColumn::make('filtered_revenue')
                    ->label('Prihod')
                    ->money('RSD', divideBy: 1)
                    ->sortable(),
            ])
            ->defaultSort('filtered_revenue', 'desc')
            ->paginated([5, 10, 25]);
    }
}
