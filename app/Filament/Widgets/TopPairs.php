<?php

namespace App\Filament\Widgets;

use App\Models\Price;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class TopPairs extends BaseWidget
{
    protected static ?int $sort = 1;


    protected function getStats(): array
    {
        // Получаем лучшие предложения для покупки
        // $bestBuy = Price::select([
        //     'currency_pairs.symbol',
        //     'exchanges.name as exchange_name',
        //     'prices.ask',
        // ])
        //     ->join('currency_pairs', 'prices.currency_pair_id', '=', 'currency_pairs.id')
        //     ->join('exchanges', 'prices.exchange_id', '=', 'exchanges.id')
        //     ->whereIn('prices.id', function ($query) {
        //         $query->select(DB::raw('MIN(id)'))
        //             ->from('prices')
        //             ->whereRaw('fetched_at >= NOW() - INTERVAL 1 HOUR')
        //             ->groupBy('currency_pair_id');
        //     })
        //     ->orderBy('ask', 'asc')
        //     ->first();

        // Получаем лучшие предложения для продажи
        // $bestSell = Price::select([
        //     'currency_pairs.symbol',
        //     'exchanges.name as exchange_name',
        //     'prices.bid',
        // ])
        //     ->join('currency_pairs', 'prices.currency_pair_id', '=', 'currency_pairs.id')
        //     ->join('exchanges', 'prices.exchange_id', '=', 'exchanges.id')
        //     ->whereIn('prices.id', function ($query) {
        //         $query->select(DB::raw('MIN(id)'))
        //             ->from('prices')
        //             ->whereRaw('fetched_at >= NOW() - INTERVAL 1 HOUR')
        //             ->groupBy('currency_pair_id');
        //     })
        //     ->orderBy('bid', 'desc')
        //     ->first();

        // Получаем пару с максимальным спредом
        // $maxSpread = Price::select([
        //     'currency_pairs.symbol',
        //     'exchanges.name as exchange_name',
        //     DB::raw('(ask - bid) as spread'),
        // ])
        //     ->join('currency_pairs', 'prices.currency_pair_id', '=', 'currency_pairs.id')
        //     ->join('exchanges', 'prices.exchange_id', '=', 'exchanges.id')
        //     ->whereRaw('fetched_at >= NOW() - INTERVAL 1 HOUR')
        //     ->orderBy('spread', 'desc')
        //     ->first();

        $bestBuy = (object)[
            'symbol' => 'BTC/USDT',
            'exchange_name' => 'Binance',
            'ask' => 10000,
        ];

        $bestSell = (object)[
            'symbol' => 'BTC/USDT',
            'exchange_name' => 'Binance',
            'bid' => 10000,
        ];

        $maxSpread = (object)[
            'symbol' => 'BTC/USDT',
            'exchange_name' => 'Binance',
            'spread' => 10000,
        ];

        return [
            Stat::make('Лучшая цена покупки', $bestBuy ? sprintf(
                '%s на %s: $%s',
                $bestBuy->symbol,
                $bestBuy->exchange_name,
                number_format($bestBuy->ask, 2)
            ) : 'Нет данных')
                ->description('За последний час')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('success'),

            Stat::make('Лучшая цена продажи', $bestSell ? sprintf(
                '%s на %s: $%s',
                $bestSell->symbol,
                $bestSell->exchange_name,
                number_format($bestSell->bid, 2)
            ) : 'Нет данных')
                ->description('За последний час')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Максимальный спред', $maxSpread ? sprintf(
                '%s на %s: $%s',
                $maxSpread->symbol,
                $maxSpread->exchange_name,
                number_format($maxSpread->spread, 2)
            ) : 'Нет данных')
                ->description('За последний час')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
