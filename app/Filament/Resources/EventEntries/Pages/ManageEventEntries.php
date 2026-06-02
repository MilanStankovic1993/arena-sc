<?php

namespace App\Filament\Resources\EventEntries\Pages;

use App\Filament\Resources\EventEntries\EventEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageEventEntries extends ManageRecords
{
    protected static string $resource = EventEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth(Width::Screen),
        ];
    }
}
