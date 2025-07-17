<?php

namespace App\Filament\Resources\ExchangeApiKeyResource\Pages;

use App\Filament\Resources\ExchangeApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExchangeApiKeys extends ListRecords
{
    protected static string $resource = ExchangeApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
