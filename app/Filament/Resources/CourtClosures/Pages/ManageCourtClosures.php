<?php

namespace App\Filament\Resources\CourtClosures\Pages;

use App\Filament\Resources\CourtClosures\CourtClosureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageCourtClosures extends ManageRecords
{
    protected static string $resource = CourtClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth(Width::Screen),
        ];
    }
}
