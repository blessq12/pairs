<?php

namespace App\Parsers;

use App\Exceptions\ExchangeParserException;

class MexcParser extends BaseExchangeParser
{
    public function getTicker(string $symbol): array
    {
        $normalizedSymbol = $this->normalizeSymbol($symbol);

        $data = $this->makeRequest($this->spotApiUrl . '/api/v3/ticker/bookTicker', [
            'symbol' => $normalizedSymbol,
        ]);

        if (!isset($data['askPrice'], $data['bidPrice'])) {
            throw new ExchangeParserException('Invalid ticker data format from MEXC');
        }

        return [
            'ask' => (float)$data['askPrice'],
            'bid' => (float)$data['bidPrice'],
        ];
    }

    public function getKline(string $symbol, string $interval): array
    {
        // Проверяем поддерживается ли интервал
        $this->validateInterval($interval);

        $normalizedSymbol = $this->normalizeSymbol($symbol);
        $normalizedInterval = $this->normalizeInterval($interval);

        $data = $this->makeRequest($this->klineApiUrl . '/api/v3/klines', [
            'symbol' => $normalizedSymbol,
            'interval' => $normalizedInterval,
            'limit' => $this->getKlineLimit(),
        ]);

        if (!is_array($data)) {
            throw new ExchangeParserException('Invalid kline data format from MEXC');
        }

        return array_map(function ($candle) {
            if (count($candle) < 6) {
                throw new ExchangeParserException('Invalid candle data format from MEXC');
            }

            return [
                'timestamp' => (int)($candle[0] / 1000), // MEXC даёт в миллисекундах
                'open' => (float)$candle[1],
                'high' => (float)$candle[2],
                'low' => (float)$candle[3],
                'close' => (float)$candle[4],
                'volume' => (float)$candle[5],
            ];
        }, $data);
    }

    protected function normalizeSymbol(string $symbol): string
    {
        // MEXC использует формат без слэша: BTC/USDT -> BTCUSDT
        return str_replace('/', '', $symbol);
    }

    protected function normalizeInterval(string $interval): string
    {
        // MEXC использует тот же формат что и мы ('1m', '5m', '15m', '30m', '1h', '4h', '1d')
        return $interval;
    }
}
