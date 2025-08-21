<?php

namespace App\Filament\Resources\ExchangePairResource\Pages;

use App\Filament\Resources\ExchangePairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExchangePair extends EditRecord
{
    protected static string $resource = ExchangePairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
