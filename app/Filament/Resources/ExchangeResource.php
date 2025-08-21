<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeResource\Pages;
use App\Models\Exchange;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExchangeResource extends Resource
{
    protected static ?string $model = Exchange::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Биржи';


    public static function getModelLabel(): string
    {
        return __('filament/resources.resources.exchange.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament/resources.resources.exchange.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название биржи')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('api_base_url')
                    ->label('Базовый URL API')
                    ->required()
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('spot_api_url')
                    ->label('URL API спот-торговли')
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('futures_api_url')
                    ->label('URL API фьючерсов')
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('kline_api_url')
                    ->label('URL API для графиков')
                    ->url()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('api_base_url')
                    ->label('Базовый API')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('spot_api_url')
                    ->label('Спот API')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('futures_api_url')
                    ->label('Фьючерсы API')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('kline_api_url')
                    ->label('Графики API')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные биржи')
                    ->placeholder('Все биржи')
                    ->trueLabel('Только активные')
                    ->falseLabel('Только неактивные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchanges::route('/'),
            'create' => Pages\CreateExchange::route('/create'),
            'edit' => Pages\EditExchange::route('/{record}/edit'),
        ];
    }
}
