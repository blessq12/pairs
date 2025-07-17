<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceResource\Pages;
use App\Models\Price;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PriceResource extends Resource
{
    protected static ?string $model = Price::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Цены';

    protected static ?string $navigationGroup = 'Управление биржами';

    public static function getModelLabel(): string
    {
        return __('filament/resources.resources.price.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament/resources.resources.price.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('exchange_id')
                    ->label('Биржа')
                    ->relationship('exchange', 'name')
                    ->required(),
                Forms\Components\Select::make('currency_pair_id')
                    ->label('Валютная пара')
                    ->relationship('currencyPair', 'symbol')
                    ->required(),
                Forms\Components\TextInput::make('bid')
                    ->label('Цена покупки')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('ask')
                    ->label('Цена продажи')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\DateTimePicker::make('fetched_at')
                    ->label('Время получения')
                    ->required(),
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
                Tables\Columns\TextColumn::make('currencyPair.symbol')
                    ->label('Валютная пара')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bid')
                    ->label('Цена покупки')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ask')
                    ->label('Цена продажи')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fetched_at')
                    ->label('Получено')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recommendation')
                    ->label('Рекомендация')
                    ->state(function (Price $record): string {
                        $bestBuy = Price::where('currency_pair_id', $record->currency_pair_id)
                            ->orderBy('ask', 'asc')
                            ->first();

                        $bestSell = Price::where('currency_pair_id', $record->currency_pair_id)
                            ->orderBy('bid', 'desc')
                            ->first();

                        if ($record->id === $bestBuy?->id) {
                            return '✅ Лучшая цена для покупки';
                        }

                        if ($record->id === $bestSell?->id) {
                            return '💰 Лучшая цена для продажи';
                        }

                        return '-';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exchange')
                    ->label('Биржа')
                    ->relationship('exchange', 'name'),
                Tables\Filters\SelectFilter::make('currency_pair')
                    ->label('Валютная пара')
                    ->relationship('currencyPair', 'symbol'),
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
            ->defaultSort('fetched_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrices::route('/'),
            'create' => Pages\CreatePrice::route('/create'),
            'edit' => Pages\EditPrice::route('/{record}/edit'),
        ];
    }
}
