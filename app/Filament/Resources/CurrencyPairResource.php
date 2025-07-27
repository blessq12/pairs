<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyPairResource\Pages;
use App\Models\CurrencyPair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CurrencyPairResource extends Resource
{
    protected static ?string $model = CurrencyPair::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Валютные пары';

    // protected static ?string $navigationGroup = 'Управление биржами';

    public static function getModelLabel(): string
    {
        return __('filament/resources.resources.currency-pair.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament/resources.resources.currency-pair.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('base_currency')
                    ->label('Базовая валюта')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('BTC')
                    ->helperText('Например: BTC, ETH, XRP'),
                Forms\Components\TextInput::make('quote_currency')
                    ->label('Котируемая валюта')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('USDT')
                    ->helperText('Например: USDT, USD, EUR'),
                Forms\Components\TextInput::make('symbol')
                    ->label('Символ пары')
                    ->disabled()
                    ->helperText('Будет создан автоматически из базовой и котируемой валют'),
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
                Tables\Columns\TextColumn::make('symbol')
                    ->label('Символ')
                    ->searchable()
                    ->sortable()
                    ->tooltip(fn(CurrencyPair $record): string => "Базовая: {$record->base_currency}\nКотируемая: {$record->quote_currency}"),
                Tables\Columns\TextColumn::make('base_currency')
                    ->label('Базовая')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quote_currency')
                    ->label('Котируемая')
                    ->searchable()
                    ->sortable(),
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
                    ->label('Активные пары')
                    ->placeholder('Все пары')
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
            ])
            ->defaultSort('symbol', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencyPairs::route('/'),
            'create' => Pages\CreateCurrencyPair::route('/create'),
            'edit' => Pages\EditCurrencyPair::route('/{record}/edit'),
        ];
    }
}
