<?php

namespace App\Filament\Resources\PricingRules\Pages;

use App\Filament\Resources\PricingRules\PricingRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManagePricingRules extends ManageRecords
{
    protected static string $resource = PricingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->modalWidth(Width::Screen),
        ];
    }
}
