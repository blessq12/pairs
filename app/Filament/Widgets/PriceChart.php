<?php

namespace App\Filament\Widgets;

use App\Models\CurrencyPair;
use App\Models\Price;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PriceChart extends ChartWidget
{
    protected static ?string $heading = 'График цен';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'height' => 400,
        ];
    }

    protected function getData(): array
    {
        $pair = CurrencyPair::find($this->filterFormData['pair_id'] ?? CurrencyPair::first()?->id);

        if (!$pair) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $startDate = Carbon::parse($this->filterFormData['start_date'] ?? now()->subDays(7)->startOfDay());
        $endDate = Carbon::parse($this->filterFormData['end_date'] ?? now()->endOfDay());

        // Генерируем моковые данные
        $dates = [];
        $bidData = [];
        $askData = [];

        $currentDate = clone $startDate;
        $basePrice = 10000; // Базовая цена

        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->format('Y-m-d H:i');

            // Генерируем случайные колебания цены
            $variation = rand(-100, 100);
            $bid = $basePrice + $variation;
            $ask = $bid + rand(10, 50); // Спред между bid и ask

            $bidData[] = $bid;
            $askData[] = $ask;

            $currentDate->addHours(4); // Каждые 4 часа новая точка
            $basePrice = $bid; // Следующая цена будет основана на предыдущей
        }

        return [
            'datasets' => [
                [
                    'label' => 'Цена покупки (BID)',
                    'data' => $bidData,
                    'borderColor' => '#10B981',
                    'fill' => false,
                ],
                [
                    'label' => 'Цена продажи (ASK)',
                    'data' => $askData,
                    'borderColor' => '#EF4444',
                    'fill' => false,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('pair_id')
                ->label('Валютная пара')
                ->options(CurrencyPair::query()->pluck('symbol', 'id'))
                ->required()
                ->reactive(),
            DatePicker::make('start_date')
                ->label('Начальная дата')
                ->default(now()->subDays(7))
                ->required()
                ->reactive(),
            DatePicker::make('end_date')
                ->label('Конечная дата')
                ->default(now())
                ->required()
                ->reactive(),
        ];
    }
}
