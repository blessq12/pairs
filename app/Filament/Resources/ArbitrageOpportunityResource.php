<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArbitrageOpportunityResource\Pages;
use App\Models\ArbitrageOpportunity;
use App\Models\Exchange;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArbitrageOpportunityResource extends Resource
{
    protected static ?string $model = ArbitrageOpportunity::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Арбитражные возможности';
    protected static ?string $modelLabel = 'Арбитражная возможность';
    protected static ?string $pluralModelLabel = 'Арбитражные возможности';
    protected static ?string $navigationGroup = 'Арбитраж';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('buy_exchange_id')
                    ->label('Биржа покупки')
                    ->options(Exchange::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\Select::make('sell_exchange_id')
                    ->label('Биржа продажи')
                    ->options(Exchange::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('base_currency')
                    ->label('Базовая валюта')
                    ->required()
                    ->maxLength(10),

                Forms\Components\TextInput::make('quote_currency')
                    ->label('Котируемая валюта')
                    ->required()
                    ->maxLength(10),

                Forms\Components\TextInput::make('buy_price')
                    ->label('Цена покупки')
                    ->numeric()
                    ->step(0.00000001)
                    ->required(),

                Forms\Components\TextInput::make('sell_price')
                    ->label('Цена продажи')
                    ->numeric()
                    ->step(0.00000001)
                    ->required(),

                Forms\Components\TextInput::make('profit_percent')
                    ->label('Процент профита')
                    ->numeric()
                    ->step(0.0001)
                    ->required(),

                Forms\Components\TextInput::make('profit_usd')
                    ->label('Профит USD')
                    ->numeric()
                    ->step(0.01)
                    ->required(),

                Forms\Components\TextInput::make('volume_24h_buy')
                    ->label('Объем 24ч покупка')
                    ->numeric()
                    ->step(0.01),

                Forms\Components\TextInput::make('volume_24h_sell')
                    ->label('Объем 24ч продажа')
                    ->numeric()
                    ->step(0.01),

                Forms\Components\TextInput::make('min_volume_usd')
                    ->label('Мин. объем USD')
                    ->numeric()
                    ->step(0.01)
                    ->required(),

                Forms\Components\TextInput::make('buy_commission')
                    ->label('Комиссия покупки')
                    ->numeric()
                    ->step(0.0001)
                    ->required(),

                Forms\Components\TextInput::make('sell_commission')
                    ->label('Комиссия продажи')
                    ->numeric()
                    ->step(0.0001)
                    ->required(),

                Forms\Components\TextInput::make('total_commission')
                    ->label('Общая комиссия')
                    ->numeric()
                    ->step(0.0001)
                    ->required(),

                Forms\Components\TextInput::make('net_profit_percent')
                    ->label('Чистый профит %')
                    ->numeric()
                    ->step(0.0001)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),

                Forms\Components\DateTimePicker::make('detected_at')
                    ->label('Обнаружено')
                    ->required(),

                Forms\Components\DateTimePicker::make('alerted_at')
                    ->label('Отправлен алерт'),

                Forms\Components\DateTimePicker::make('expired_at')
                    ->label('Истекает'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('buyExchange.name')
                    ->label('Биржа покупки')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('sellExchange.name')
                    ->label('Биржа продажи')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('symbol')
                    ->label('Пара')
                    ->formatStateUsing(fn(ArbitrageOpportunity $record): string => $record->base_currency . '/' . $record->quote_currency)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('buy_price')
                    ->label('Цена покупки')
                    ->numeric(
                        decimalPlaces: 8,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('sell_price')
                    ->label('Цена продажи')
                    ->numeric(
                        decimalPlaces: 8,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_profit_percent')
                    ->label('Профит %')
                    ->numeric(
                        decimalPlaces: 4,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->color(fn(ArbitrageOpportunity $record): string => $record->net_profit_percent > 0 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('profit_usd')
                    ->label('Профит USD')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('detected_at')
                    ->label('Обнаружено')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('alerted_at')
                    ->label('Алерт')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('buy_exchange_id')
                    ->label('Биржа покупки')
                    ->options(Exchange::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('sell_exchange_id')
                    ->label('Биржа продажи')
                    ->options(Exchange::pluck('name', 'id')),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Статус')
                    ->placeholder('Все')
                    ->trueLabel('Активные')
                    ->falseLabel('Неактивные'),

                Tables\Filters\Filter::make('profitable')
                    ->label('Только прибыльные')
                    ->query(fn(Builder $query): Builder => $query->where('net_profit_percent', '>', 0)),
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
                        ->action(fn($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('detected_at', 'desc');
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
            'index' => Pages\ListArbitrageOpportunities::route('/'),
            'create' => Pages\CreateArbitrageOpportunity::route('/create'),
            'edit' => Pages\EditArbitrageOpportunity::route('/{record}/edit'),
        ];
    }
}
