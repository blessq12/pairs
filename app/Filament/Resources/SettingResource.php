<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Настройки';
    protected static ?string $navigationGroup = 'Система';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([




                Forms\Components\Section::make('Арбитраж')
                    ->schema([
                        Forms\Components\TextInput::make('min_profit_percent')
                            ->label('Минимальный профит (%)')
                            ->numeric()
                            ->minValue(0.1)
                            ->step(0.1)
                            ->required(),
                        Forms\Components\TextInput::make('min_volume_usd')
                            ->label('Минимальный объём (USD)')
                            ->numeric()
                            ->minValue(1)
                            ->step(1)
                            ->required(),
                        Forms\Components\TextInput::make('alert_cooldown_minutes')
                            ->label('Задержка алертов (минуты)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('poll_interval_minutes')
                            ->label('Интервал опроса (минуты)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Комиссии бирж')
                    ->schema([
                        Forms\Components\TextInput::make('mexc_commission')
                            ->label('MEXC комиссия')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->required(),
                        Forms\Components\TextInput::make('bybit_commission')
                            ->label('Bybit комиссия')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->required(),
                        Forms\Components\TextInput::make('bingx_commission')
                            ->label('BingX комиссия')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->required(),
                        Forms\Components\TextInput::make('coinex_commission')
                            ->label('CoinEx комиссия')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.0001)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Telegram')
                    ->schema([
                        Forms\Components\TextInput::make('telegram_bot_token')
                            ->label('Токен бота')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? $state : null),
                        Forms\Components\TextInput::make('telegram_chat_id')
                            ->label('ID чата'),
                        Forms\Components\TextInput::make('telegram_message_template')
                            ->label('Шаблон сообщения')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Хранение данных')
                    ->schema([
                        Forms\Components\TextInput::make('price_history_days')
                            ->label('Период хранения цен (дни)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\Toggle::make('price_cleanup_enabled')
                            ->label('Включить очистку старых цен')
                            ->required(),
                    ]),


            ]);
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\EditSetting::route('/'),
        ];
    }
}
