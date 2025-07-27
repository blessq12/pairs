<?php

namespace App\Console\Commands;

use App\Models\Exchange;
use Illuminate\Console\Command;

class InitExchanges extends Command
{
    protected $signature = 'pairs:init-exchanges';
    protected $description = 'Инициализация бирж с их API URL';

    private const EXCHANGES = [
        [
            'name' => 'MEXC',
            'api_base_url' => 'https://api.mexc.com',
            'spot_api_url' => 'https://api.mexc.com/api/v3/ticker/price',
            'futures_api_url' => 'https://contract.mexc.com/api/v1/contract/ticker',
            'kline_api_url' => 'https://api.mexc.com/api/v3/klines',
            'is_active' => true,
        ],
        [
            'name' => 'Bybit',
            'api_base_url' => 'https://api.bybit.com',
            'spot_api_url' => 'https://api.bybit.com/v5/market/tickers?category=spot',
            'futures_api_url' => 'https://api.bybit.com/v5/market/tickers?category=linear',
            'kline_api_url' => 'https://api.bybit.com/v5/market/kline?category=spot',
            'is_active' => true,
        ],
        [
            'name' => 'BingX',
            'api_base_url' => 'https://open-api.bingx.com',
            'spot_api_url' => 'https://open-api.bingx.com/openApi/api/v3/market/ticker',
            'futures_api_url' => 'https://open-api.bingx.com/openApi/api/v3/market/ticker?type=PERPETUAL',
            'kline_api_url' => 'https://open-api.bingx.com/openApi/api/v3/market/kline',
            'is_active' => true,
        ],
        [
            'name' => 'CoinEx',
            'api_base_url' => 'https://api.coinex.com',
            'spot_api_url' => 'https://api.coinex.com/v2/market/ticker',
            'futures_api_url' => 'https://api.coinex.com/perpetual/v2/market/ticker',
            'kline_api_url' => 'https://api.coinex.com/v2/market/kline',
            'is_active' => true,
        ],
    ];

    public function handle(): void
    {
        $this->info('🚀 Начинаем инициализацию бирж...');

        foreach (self::EXCHANGES as $exchangeData) {
            $exchange = Exchange::firstOrCreate(
                ['name' => $exchangeData['name']],
                $exchangeData
            );

            if ($exchange->wasRecentlyCreated) {
                $this->info("✅ Создана биржа: {$exchangeData['name']}");
            } else {
                $exchange->update($exchangeData);
                $this->info("🔄 Обновлена биржа: {$exchangeData['name']}");
            }
        }

        $this->info('✨ Инициализация бирж завершена!');

        // Выводим статистику
        $this->newLine();
        $this->table(
            ['Биржа', 'Базовый URL', 'Статус'],
            Exchange::all()->map(fn($exchange) => [
                $exchange->name,
                $exchange->api_base_url,
                $exchange->is_active ? '✅ Активна' : '❌ Неактивна'
            ])
        );
    }
}
