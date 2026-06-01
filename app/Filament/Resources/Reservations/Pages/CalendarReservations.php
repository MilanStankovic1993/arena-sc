<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Filament\Resources\Reservations\ReservationResource;
use App\Filament\Widgets\CalendarReservationsWidget;
use Filament\Resources\Pages\Page;

class CalendarReservations extends Page
{
    protected static string $resource = ReservationResource::class;

    protected string $view = 'filament.resources.reservations.pages.calendar-reservations';

    protected ?string $heading = 'Kalendar rezervacija';

    protected ?string $subheading = 'Pregled termina po danu, nedelji i mesecu. U kalendaru se prikazuje samo naziv korisnika.';

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarReservationsWidget::class,
        ];
    }
}
