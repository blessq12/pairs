<?php

namespace App\Filament\Resources\ArbitrageOpportunityResource\Pages;

use App\Filament\Resources\ArbitrageOpportunityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArbitrageOpportunity extends EditRecord
{
    protected static string $resource = ArbitrageOpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
