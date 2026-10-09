<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Filament\Resources\Reservations\PhoneReservationForm;
use App\Filament\Resources\Reservations\ReservationResource;
use Filament\Resources\Pages\ManageRecords;

class ManageReservations extends ManageRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PhoneReservationForm::action(),
        ];
    }
}
