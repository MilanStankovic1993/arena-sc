<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Filament\Resources\Reservations\PhoneReservationForm;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Services\ReservationListService;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ManageReservations extends ManageRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PhoneReservationForm::action(),
        ];
    }

    protected function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    {
        if ($this->getTableGrouping()?->getId() !== 'reservation_customer_group') {
            return parent::paginateTableQuery($query);
        }

        return app(ReservationListService::class)->paginateGroups(
            $query,
            (int) $this->getTableRecordsPerPage(),
            (int) $this->getTablePage(),
            $this->getTablePaginationPageName(),
        );
    }
}
