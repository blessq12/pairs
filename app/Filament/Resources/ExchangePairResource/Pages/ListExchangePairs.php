<?php

namespace App\Filament\Resources\ExchangePairResource\Pages;

use App\Filament\Resources\ExchangePairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExchangePairs extends ListRecords
{
    protected static string $resource = ExchangePairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
