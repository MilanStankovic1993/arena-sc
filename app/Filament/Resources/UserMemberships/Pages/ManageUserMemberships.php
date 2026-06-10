<?php

namespace App\Filament\Resources\UserMemberships\Pages;

use App\Filament\Resources\UserMemberships\UserMembershipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageUserMemberships extends ManageRecords
{
    protected static string $resource = UserMembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth(Width::Screen),
        ];
    }
}
