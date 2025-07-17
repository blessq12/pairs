<?php

namespace App\Filament\Resources\CurrencyPairResource\Pages;

use App\Filament\Resources\CurrencyPairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurrencyPair extends EditRecord
{
    protected static string $resource = CurrencyPairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
