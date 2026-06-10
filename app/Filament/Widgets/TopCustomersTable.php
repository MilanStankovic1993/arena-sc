<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;

class TopCustomersTable extends TableWidget
{
    protected static ?string $heading = 'Top korisnici po potrosnji';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::query()
                ->where('role', 'customer')
                ->withSum('participatedReservations as participated_revenue', 'total_price'))
            ->columns([
                TextColumn::make('name')->label('Korisnik')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('total_reservations')->label('Rezervacije')->sortable(),
                TextColumn::make('participated_revenue')->label('Potrosnja')->money('RSD', divideBy: 1)->sortable(),
            ])
            ->defaultSort('participated_revenue', 'desc')
            ->paginated([5, 10, 25]);
    }
}
