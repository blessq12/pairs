<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'Дашборд';
    protected static ?string $slug = 'dashboard';
    protected static string $view = 'filament.pages.dashboard';
}
