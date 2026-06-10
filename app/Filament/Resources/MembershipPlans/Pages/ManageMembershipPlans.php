<?php

namespace App\Filament\Resources\MembershipPlans\Pages;

use App\Filament\Resources\MembershipPlans\MembershipPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageMembershipPlans extends ManageRecords
{
    protected static string $resource = MembershipPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth(Width::Screen),
        ];
    }
}
