<?php

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Ukupne rezervacije', (string) Reservation::query()->count())
                ->description('Sve kreirane rezervacije'),
            Stat::make(
                'Prihod od termina',
                number_format((float) Reservation::query()->whereIn('status', [
                    ReservationStatus::Approved->value,
                    ReservationStatus::Completed->value,
                ])->sum('total_price'), 0, ',', '.').' RSD'
            )->description('Odobrene i realizovane rezervacije'),
            Stat::make('Aktivni korisnici', (string) User::query()->where('role', 'customer')->count())
                ->description('Registrovani korisnici za rezervacije'),
            Stat::make('Aktivna oprema', (string) Equipment::query()->where('is_active', true)->count())
                ->description('Artikli na sajtu'),
        ];
    }
}
