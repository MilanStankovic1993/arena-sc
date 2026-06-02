<?php

namespace App\Filament\Resources\EventMatches\Pages;

use App\Filament\Resources\EventMatches\EventMatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageEventMatches extends ManageRecords
{
    protected static string $resource = EventMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth(Width::Screen),
        ];
    }
}
