<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeApiKeyResource\Pages;
use App\Models\ExchangeApiKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExchangeApiKeyResource extends Resource
{
    protected static ?string $model = ExchangeApiKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Ключи';

    protected static ?string $navigationGroup = 'Управление биржами';

    public static function getModelLabel(): string
    {
        return __('filament/resources.resources.exchange-api-key.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament/resources.resources.exchange-api-key.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('exchange_id')
                    ->label('Биржа')
                    ->relationship('exchange', 'name')
                    ->required(),
                Forms\Components\TextInput::make('api_key')
                    ->label('API Ключ')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('api_secret')
                    ->label('API Секрет')
                    ->required()
                    ->password() // Скрываем ввод
                    ->dehydrateStateUsing(fn($state) => encrypt($state)) // Шифруем перед сохранением
                    ->dehydrated(fn($state) => filled($state))
                    ->maxLength(255),
                Forms\Components\Textarea::make('additional_params')
                    ->label('Дополнительные параметры (JSON)')
                    ->helperText('Введите дополнительные параметры в формате JSON')
                    ->json(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->maxLength(65535),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exchange.name')
                    ->label('Биржа')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('api_key')
                    ->label('API Ключ')
                    ->searchable()
                    ->formatStateUsing(fn(string $state): string => substr($state, 0, 4) . '...' . substr($state, -4)), // Показываем только часть ключа
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exchange')
                    ->label('Биржа')
                    ->relationship('exchange', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность')
                    ->boolean()
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchangeApiKeys::route('/'),
            'create' => Pages\CreateExchangeApiKey::route('/create'),
            'edit' => Pages\EditExchangeApiKey::route('/{record}/edit'),
        ];
    }
}
