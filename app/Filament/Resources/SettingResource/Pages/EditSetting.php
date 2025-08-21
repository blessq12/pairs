<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use App\Models\Setting;
use Filament\Resources\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class EditSetting extends Page
{
    use InteractsWithForms;

    protected static string $resource = SettingResource::class;
    protected static ?string $title = 'Настройки системы';
    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::firstOrCreate();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return SettingResource::form($form)
            ->statePath('data');
    }

    public function save(): void
    {
        $settings = Setting::firstOrCreate();
        $settings->update($this->form->getState());
        Setting::flushCache();

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }
}
