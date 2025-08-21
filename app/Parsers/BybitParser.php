<?php

namespace App\Parsers;

use App\Exceptions\ExchangeParserException;
use Illuminate\Support\Facades\Log;

class BybitParser extends BaseExchangeParser
{
    public function getTicker(string $symbol): array
    {
        $normalizedSymbol = $this->normalizeSymbol($symbol);

        $data = $this->makeRequest($this->spotApiUrl, [
            'category' => 'spot',
            'symbol' => $normalizedSymbol,
        ]);

        if (!isset($data['result']['list'][0])) {
            throw new ExchangeParserException('Invalid ticker data format from Bybit');
        }

        $ticker = $data['result']['list'][0];

        if (!isset($ticker['ask1Price'], $ticker['bid1Price'])) {
            throw new ExchangeParserException('Missing ask/bid prices in Bybit ticker data');
        }

        return [
            'ask' => (float)$ticker['ask1Price'],
            'bid' => (float)$ticker['bid1Price'],
        ];
    }

    public function getKline(string $symbol, string $interval): array
    {
        $this->validateInterval($interval);

        $normalizedSymbol = $this->normalizeSymbol($symbol);
        $normalizedInterval = $this->normalizeInterval($interval);

        $data = $this->makeRequest($this->klineApiUrl, [
            'category' => 'spot',
            'symbol' => $normalizedSymbol,
            'interval' => $normalizedInterval,
            'limit' => $this->getKlineLimit(),
        ]);

        if (!isset($data['result']['list'])) {
            throw new ExchangeParserException('Invalid kline data format from Bybit');
        }

        return array_map(function ($candle) {
            if (count($candle) < 6) {
                throw new ExchangeParserException('Invalid candle data format from Bybit');
            }

            return [
                'timestamp' => (int)($candle[0] / 1000), // Bybit даёт в миллисекундах
                'open' => (float)$candle[1],
                'high' => (float)$candle[2],
                'low' => (float)$candle[3],
                'close' => (float)$candle[4],
                'volume' => (float)$candle[5],
            ];
        }, $data['result']['list']);
    }

    protected function normalizeSymbol(string $symbol): string
    {
        // Bybit использует формат без слэша: BTC/USDT -> BTCUSDT
        return str_replace('/', '', $symbol);
    }

    protected function normalizeInterval(string $interval): string
    {
        // Bybit использует тот же формат что и мы
        return $interval;
    }

    /**
     * Получает объём торгов за 24 часа для указанной пары
     */
    public function get24hVolume(string $symbol): ?float
    {
        $normalizedSymbol = $this->normalizeSymbol($symbol);

        try {
            $data = $this->makeRequest($this->spotApiUrl, [
                'category' => 'spot',
                'symbol' => $normalizedSymbol,
            ]);

            if (!isset($data['result']['list'][0])) {
                return null;
            }

            $ticker = $data['result']['list'][0];

            // Bybit возвращает объём в quote currency (USDT)
            return isset($ticker['volume24h']) ? (float)$ticker['volume24h'] : null;
        } catch (\Exception $e) {
            Log::warning("Ошибка при получении объёма для {$symbol} с Bybit: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Получает список всех доступных торговых пар
     */
    public function getAllSymbols(): array
    {
        try {
            $data = $this->makeRequest($this->spotApiUrl, [
                'category' => 'spot',
            ]);

            if (!isset($data['result']['list'])) {
                throw new ExchangeParserException('Invalid symbols data format from Bybit');
            }

            $symbols = [];
            foreach ($data['result']['list'] as $item) {
                if (isset($item['symbol'])) {
                    $symbols[] = $item['symbol'];
                }
            }

            return $symbols;
        } catch (\Exception $e) {
            Log::error("Ошибка при получении списка пар с Bybit: {$e->getMessage()}");
            throw new ExchangeParserException("Failed to get symbols from Bybit: {$e->getMessage()}");
        }
    }
}
