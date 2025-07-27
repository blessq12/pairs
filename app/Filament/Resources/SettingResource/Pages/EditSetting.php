<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;
    protected static ?string $title = 'Редактирование настроек';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        Setting::flushCache();
    }
}
