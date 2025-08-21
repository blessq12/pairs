<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <x-filament::button type="submit" style="margin-top: 24px;">
            Сохранить настройки
        </x-filament::button>
    </form>
</x-filament-panels::page>
