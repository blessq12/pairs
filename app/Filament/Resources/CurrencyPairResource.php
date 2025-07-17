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

    protected static ?string $navigationGroup = 'Управление биржами';

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
                Forms\Components\TextInput::make('symbol')
                    ->label('Символ')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('base_currency')
                    ->label('Базовая валюта')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('quote_currency')
                    ->label('Котируемая валюта')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('symbol')
                    ->label('Символ')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_currency')
                    ->label('Базовая валюта')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quote_currency')
                    ->label('Котируемая валюта')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
