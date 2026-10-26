<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCustomersTable extends TableWidget
{
    protected static ?string $heading = 'Top korisnici po potrosnji';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()
                ->where('role', 'customer')
                ->withCount(['participatedReservations as commercial_reservations_count' => fn (Builder $query) => $query->where('total_price', '>', 0)])
                ->withSum(['participatedReservations as participated_revenue' => fn (Builder $query) => $query->where('total_price', '>', 0)], 'total_price'))
            ->columns([
                TextColumn::make('name')->label('Korisnik')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('commercial_reservations_count')->label('Rezervacije (bez besplatnih)')->sortable(),
                TextColumn::make('participated_revenue')->label('Potrosnja')->money('RSD', divideBy: 1)->sortable(),
            ])
            ->defaultSort('participated_revenue', 'desc')
            ->paginated([5, 10, 25]);
    }
}
