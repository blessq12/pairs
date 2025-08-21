<?php

namespace App\Parsers;

use App\Exceptions\ExchangeParserException;

class CoinExParser extends BaseExchangeParser
{
    public function getTicker(string $symbol): array
    {
        $normalizedSymbol = $this->normalizeSymbol($symbol);

        $data = $this->makeRequest($this->spotApiUrl, [
            'market' => $normalizedSymbol,
        ]);

        if (!isset($data['data']['ticker'])) {
            throw new ExchangeParserException('Invalid ticker data format from CoinEx');
        }

        $ticker = $data['data']['ticker'];

        if (!isset($ticker['sell'], $ticker['buy'])) {
            throw new ExchangeParserException('Missing ask/bid prices in CoinEx ticker data');
        }

        return [
            'ask' => (float)$ticker['sell'],
            'bid' => (float)$ticker['buy'],
        ];
    }

    public function getKline(string $symbol, string $interval): array
    {
        $this->validateInterval($interval);

        $normalizedSymbol = $this->normalizeSymbol($symbol);
        $normalizedInterval = $this->normalizeInterval($interval);

        $data = $this->makeRequest($this->klineApiUrl, [
            'market' => $normalizedSymbol,
            'type' => $normalizedInterval,
            'limit' => $this->getKlineLimit(),
        ]);

        if (!isset($data['data'])) {
            throw new ExchangeParserException('Invalid kline data format from CoinEx');
        }

        return array_map(function ($candle) {
            if (count($candle) < 6) {
                throw new ExchangeParserException('Invalid candle data format from CoinEx');
            }

            return [
                'timestamp' => (int)($candle[0] / 1000), // CoinEx даёт в миллисекундах
                'open' => (float)$candle[1],
                'high' => (float)$candle[2],
                'low' => (float)$candle[3],
                'close' => (float)$candle[4],
                'volume' => (float)$candle[5],
            ];
        }, $data['data']);
    }

    protected function normalizeSymbol(string $symbol): string
    {
        // CoinEx использует формат без слэша: BTC/USDT -> BTCUSDT
        return str_replace('/', '', $symbol);
    }

    protected function normalizeInterval(string $interval): string
    {
        // CoinEx использует тот же формат что и мы
        return $interval;
    }

    /**
     * Получает список всех доступных торговых пар
     */
    public function getAllSymbols(): array
    {
        try {
            $data = $this->makeRequest('https://api.coinex.com/v1/market/info', []);

            if (!isset($data['data'])) {
                throw new ExchangeParserException('Invalid symbols data format from CoinEx');
            }

            $symbols = [];
            foreach ($data['data'] as $market => $info) {
                if (isset($info['name'])) {
                    $symbols[] = $info['name'];
                }
            }

            return $symbols;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Ошибка при получении списка пар с CoinEx: {$e->getMessage()}");
            throw new ExchangeParserException("Failed to get symbols from CoinEx: {$e->getMessage()}");
        }
    }
}
