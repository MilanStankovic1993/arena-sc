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
    protected ?string $pollingInterval = '5s';

    protected function getStats(): array
    {
        $asOf = now();
        $revenue = Reservation::query()
            ->where('is_gift', false)
            ->where('total_price', '>', 0)
            ->where('status', ReservationStatus::Reserved->value)
            ->selectRaw('COALESCE(SUM(CASE WHEN ends_at <= ? THEN total_price ELSE 0 END), 0) as completed_revenue', [$asOf])
            ->selectRaw('COALESCE(SUM(CASE WHEN starts_at >= ? THEN total_price ELSE 0 END), 0) as future_value', [$asOf])
            ->selectRaw('COALESCE(SUM(CASE WHEN starts_at < ? AND ends_at > ? THEN total_price ELSE 0 END), 0) as ongoing_value', [$asOf, $asOf])
            ->selectRaw('COALESCE(SUM(CASE WHEN ends_at <= ? THEN 1 ELSE 0 END), 0) as completed_count', [$asOf])
            ->selectRaw('COALESCE(SUM(CASE WHEN starts_at >= ? THEN 1 ELSE 0 END), 0) as future_count', [$asOf])
            ->selectRaw('COALESCE(SUM(CASE WHEN starts_at < ? AND ends_at > ? THEN 1 ELSE 0 END), 0) as ongoing_count', [$asOf, $asOf])
            ->first();

        return [
            Stat::make('Odigrani termini', (string) $revenue->completed_count)
                ->description('Završeni, neotkazani, bez besplatnih')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Budući termini', (string) $revenue->future_count)
                ->description('Još nisu počeli, bez besplatnih')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('Termini u toku', (string) $revenue->ongoing_count)
                ->description('Počeli, nisu završeni, bez besplatnih')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make(
                'Prihod od odigranih termina',
                number_format((float) $revenue->completed_revenue, 0, ',', '.').' RSD'
            )->description('Završeni i neotkazani termini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make(
                'Vrednost budućih rezervacija',
                number_format((float) $revenue->future_value, 0, ',', '.').' RSD'
            )->description('Termini koji još nisu počeli')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make(
                'Vrednost termina u toku',
                number_format((float) $revenue->ongoing_value, 0, ',', '.').' RSD'
            )->description('Počeli su, ali još nisu završeni')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Aktivni korisnici', (string) User::query()->where('role', 'customer')->count())
                ->description('Registrovani korisnici za rezervacije')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
            Stat::make('Aktivna oprema', (string) Equipment::query()->where('is_active', true)->count())
                ->description('Artikli na sajtu')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('gray'),
        ];
    }
}
