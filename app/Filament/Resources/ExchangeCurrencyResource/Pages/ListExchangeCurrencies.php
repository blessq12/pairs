<?php

namespace App\Filament\Resources\ExchangeCurrencyResource\Pages;

use App\Filament\Resources\ExchangeCurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExchangeCurrencies extends ListRecords
{
    protected static string $resource = ExchangeCurrencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
