<?php

namespace App\Parsers;

use App\Exceptions\ExchangeParserException;

class BingXParser extends BaseExchangeParser
{
    public function getTicker(string $symbol): array
    {
        $normalizedSymbol = $this->normalizeSymbol($symbol);

        $data = $this->makeRequest($this->spotApiUrl, [
            'symbol' => $normalizedSymbol,
        ]);

        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new ExchangeParserException('Invalid ticker data format from BingX');
        }

        $ticker = $data['data'][0] ?? $data['data'];

        if (!isset($ticker['ask'], $ticker['bid'])) {
            throw new ExchangeParserException('Missing ask/bid prices in BingX ticker data');
        }

        return [
            'ask' => (float)$ticker['ask'],
            'bid' => (float)$ticker['bid'],
        ];
    }

    public function getKline(string $symbol, string $interval): array
    {
        $this->validateInterval($interval);

        $normalizedSymbol = $this->normalizeSymbol($symbol);
        $normalizedInterval = $this->normalizeInterval($interval);

        $data = $this->makeRequest($this->klineApiUrl, [
            'symbol' => $normalizedSymbol,
            'interval' => $normalizedInterval,
            'limit' => $this->getKlineLimit(),
        ]);

        if (!isset($data['data'])) {
            throw new ExchangeParserException('Invalid kline data format from BingX');
        }

        return array_map(function ($candle) {
            if (count($candle) < 6) {
                throw new ExchangeParserException('Invalid candle data format from BingX');
            }

            return [
                'timestamp' => (int)($candle[0] / 1000), // BingX даёт в миллисекундах
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
        // BingX использует формат без слэша: BTC/USDT -> BTCUSDT
        return str_replace('/', '', $symbol);
    }

    protected function normalizeInterval(string $interval): string
    {
        // BingX использует тот же формат что и мы
        return $interval;
    }
}
