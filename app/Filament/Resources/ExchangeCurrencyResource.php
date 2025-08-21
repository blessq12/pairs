<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeCurrencyResource\Pages;
use App\Filament\Resources\ExchangeCurrencyResource\RelationManagers;
use App\Models\ExchangeCurrency;
use App\Models\Exchange;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class ExchangeCurrencyResource extends Resource
{
    protected static ?string $model = ExchangeCurrency::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Валюты бирж';
    protected static ?string $modelLabel = 'Валюта биржи';
    protected static ?string $pluralModelLabel = 'Валюты бирж';
    protected static ?string $navigationGroup = 'Биржи';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('exchange_id')
                    ->label('Биржа')
                    ->options(Exchange::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('currency_symbol')
                    ->label('Символ валюты')
                    ->required()
                    ->maxLength(10)
                    ->helperText('Например: BTC, USDT, ETH'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
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

                Tables\Columns\TextColumn::make('currency_symbol')
                    ->label('Символ валюты')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

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
            ->defaultSort('currency_symbol');
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
            'index' => Pages\ListExchangeCurrencies::route('/'),
            'create' => Pages\CreateExchangeCurrency::route('/create'),
            'edit' => Pages\EditExchangeCurrency::route('/{record}/edit'),
        ];
    }
}
