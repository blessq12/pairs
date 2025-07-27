<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                Forms\Components\Section::make('API')
                    ->schema([
                        Forms\Components\TextInput::make('poll_interval')
                            ->label('Интервал опроса API (секунды)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('api_timeout')
                            ->label('Таймаут запросов к API (секунды)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('retry_attempts')
                            ->label('Количество попыток повтора')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Уведомления')
                    ->schema([
                        Forms\Components\TextInput::make('profit_threshold')
                            ->label('Минимальная разница ask/bid (%)')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\Toggle::make('notification_enabled')
                            ->label('Включить уведомления')
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

                Forms\Components\Section::make('Дашборд')
                    ->schema([
                        Forms\Components\TextInput::make('dashboard_refresh_interval')
                            ->label('Интервал обновления (секунды)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('top_pairs_limit')
                            ->label('Количество пар в TopPairs')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // API Settings
                Tables\Columns\TextColumn::make('poll_interval')
                    ->label('Интервал опроса')
                    ->suffix(' сек')
                    ->numeric(),
                Tables\Columns\TextColumn::make('api_timeout')
                    ->label('Таймаут API')
                    ->suffix(' сек')
                    ->numeric(),
                Tables\Columns\TextColumn::make('retry_attempts')
                    ->label('Попытки повтора')
                    ->numeric(),

                // Notification Settings
                Tables\Columns\TextColumn::make('profit_threshold')
                    ->label('Порог профита')
                    ->suffix('%')
                    ->numeric(),
                Tables\Columns\IconColumn::make('notification_enabled')
                    ->label('Уведомления')
                    ->boolean(),

                // Data Storage Settings
                Tables\Columns\TextColumn::make('price_history_days')
                    ->label('Хранение цен')
                    ->suffix(' дней')
                    ->numeric(),
                Tables\Columns\IconColumn::make('price_cleanup_enabled')
                    ->label('Очистка цен')
                    ->boolean(),

                // Dashboard Settings
                Tables\Columns\TextColumn::make('dashboard_refresh_interval')
                    ->label('Обновление')
                    ->suffix(' сек')
                    ->numeric(),
                Tables\Columns\TextColumn::make('top_pairs_limit')
                    ->label('Топ пар')
                    ->numeric(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
