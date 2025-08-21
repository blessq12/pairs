<?php

namespace App\Filament\Resources\ArbitrageOpportunityResource\Pages;

use App\Filament\Resources\ArbitrageOpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArbitrageOpportunities extends ListRecords
{
    protected static string $resource = ArbitrageOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
