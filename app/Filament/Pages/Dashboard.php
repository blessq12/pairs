<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PriceChart;
use App\Filament\Widgets\TopPairs;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Панель управления';
    protected static ?string $navigationLabel = 'Панель управления';
    protected static ?string $slug = 'dashboard';
    protected static string $view = 'filament.pages.dashboard';


    protected function getHeaderWidgets(): array
    {
        return [
            TopPairs::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            PriceChart::class,
        ];
    }
}
