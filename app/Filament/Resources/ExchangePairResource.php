<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangePairResource\Pages;
use App\Filament\Resources\ExchangePairResource\RelationManagers;
use App\Models\ExchangePair;
use App\Models\Exchange;
use App\Models\ExchangeCurrency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class ExchangePairResource extends Resource
{
    protected static ?string $model = ExchangePair::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Пары для арбитража';
    protected static ?string $modelLabel = 'Пара для арбитража';
    protected static ?string $pluralModelLabel = 'Пары для арбитража';
    protected static ?string $navigationGroup = 'Арбитраж';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('exchange_id')
                    ->label('Биржа')
                    ->options(Exchange::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('base_currency')
                    ->label('Базовая валюта')
                    ->options(function () {
                        $currencies = ExchangeCurrency::getAllUniqueCurrencies();
                        return array_combine($currencies, $currencies);
                    })
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('quote_currency')
                    ->label('Котируемая валюта')
                    ->options(function () {
                        $currencies = ExchangeCurrency::getAllUniqueCurrencies();
                        return array_combine($currencies, $currencies);
                    })
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('symbol_on_exchange')
                    ->label('Символ на бирже')
                    ->required()
                    ->maxLength(20)
                    ->helperText('Например: BTCUSDT, ETHBTC')
                    ->default(function () {
                        return '';
                    })
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $baseCurrency = $get('base_currency');
                        $quoteCurrency = $get('quote_currency');
                        if ($baseCurrency && $quoteCurrency) {
                            $set('symbol_on_exchange', $baseCurrency . $quoteCurrency);
                        }
                    }),

                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),

                Forms\Components\TextInput::make('min_amount')
                    ->label('Минимальный объем')
                    ->numeric()
                    ->step(0.00000001)
                    ->helperText('Минимальный объем для торговли'),

                Forms\Components\TextInput::make('maker_fee')
                    ->label('Комиссия мейкера')
                    ->numeric()
                    ->step(0.000001)
                    ->helperText('Например: 0.001 = 0.1%'),

                Forms\Components\TextInput::make('taker_fee')
                    ->label('Комиссия тейкера')
                    ->numeric()
                    ->step(0.000001)
                    ->helperText('Например: 0.001 = 0.1%'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exchange.name')
                    ->label('Биржа')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('base_currency')
                    ->label('Базовая валюта')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('quote_currency')
                    ->label('Котируемая валюта')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('symbol')
                    ->label('Пара')
                    ->formatStateUsing(fn(ExchangePair $record): string => $record->base_currency . '/' . $record->quote_currency)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('symbol_on_exchange')
                    ->label('Символ на бирже')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Мин. объем')
                    ->numeric(
                        decimalPlaces: 8,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    ),

                Tables\Columns\TextColumn::make('maker_fee')
                    ->label('Мейкер')
                    ->numeric(
                        decimalPlaces: 6,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->formatStateUsing(fn($state) => $state ? ($state * 100) . '%' : '-'),

                Tables\Columns\TextColumn::make('taker_fee')
                    ->label('Тейкер')
                    ->numeric(
                        decimalPlaces: 6,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->formatStateUsing(fn($state) => $state ? ($state * 100) . '%' : '-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exchange_id')
                    ->label('Биржа')
                    ->options(Exchange::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('base_currency')
                    ->label('Базовая валюта')
                    ->options(function () {
                        $currencies = ExchangeCurrency::getAllUniqueCurrencies();
                        return array_combine($currencies, $currencies);
                    }),

                Tables\Filters\SelectFilter::make('quote_currency')
                    ->label('Котируемая валюта')
                    ->options(function () {
                        $currencies = ExchangeCurrency::getAllUniqueCurrencies();
                        return array_combine($currencies, $currencies);
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('exchange_id')
            ->defaultSort('base_currency');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExchangePairs::route('/'),
            'create' => Pages\CreateExchangePair::route('/create'),
            'edit' => Pages\EditExchangePair::route('/{record}/edit'),
        ];
    }
}
