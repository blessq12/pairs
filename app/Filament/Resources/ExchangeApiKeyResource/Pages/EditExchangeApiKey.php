<?php

namespace App\Filament\Resources\ExchangeApiKeyResource\Pages;

use App\Filament\Resources\ExchangeApiKeyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExchangeApiKey extends EditRecord
{
    protected static string $resource = ExchangeApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
