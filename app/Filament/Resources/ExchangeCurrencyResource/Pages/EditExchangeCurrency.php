<?php

namespace App\Filament\Resources\ExchangeCurrencyResource\Pages;

use App\Filament\Resources\ExchangeCurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExchangeCurrency extends EditRecord
{
    protected static string $resource = ExchangeCurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
