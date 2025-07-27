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
    // protected static ?string $navigationGroup = 'Управление биржами';

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
                Forms\Components\TextInput::make('bid_price')
                    ->label('Цена покупки')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.00000001)
                    ->default(0),
                Forms\Components\TextInput::make('ask_price')
                    ->label('Цена продажи')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.00000001)
                    ->default(0),
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
                Tables\Columns\TextColumn::make('bid_price')
                    ->label('Цена покупки')
                    ->numeric(
                        decimalPlaces: 8,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('ask_price')
                    ->label('Цена продажи')
                    ->numeric(
                        decimalPlaces: 8,
                        decimalSeparator: '.',
                        thousandsSeparator: ' ',
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Получено')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('spread')
                    ->label('Спред')
                    ->state(function (Price $record): string {
                        $spread = $record->ask_price - $record->bid_price;
                        $spreadPercent = ($spread / $record->bid_price) * 100;
                        return number_format($spreadPercent, 2) . '%';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            $query->raw('(ask_price - bid_price) / bid_price * 100'),
                            $direction
                        );
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('exchange')
                    ->label('Биржа')
                    ->relationship('exchange', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('currency_pair')
                    ->label('Валютная пара')
                    ->relationship('currencyPair', 'symbol')
                    ->multiple()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DateTimePicker::make('created_from')
                            ->label('От'),
                        Forms\Components\DateTimePicker::make('created_until')
                            ->label('До'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->where('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->where('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
