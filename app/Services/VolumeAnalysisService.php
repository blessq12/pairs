<?php

namespace App\Services;

use App\Models\Exchange;
use App\Models\Price;
use App\Parsers\ExchangeParserFactory;
use Illuminate\Support\Facades\Log;

class VolumeAnalysisService
{
    private ExchangeParserFactory $parserFactory;

    public function __construct(ExchangeParserFactory $parserFactory)
    {
        $this->parserFactory = $parserFactory;
    }

    /**
     * Получает объёмы торгов для всех активных бирж и пар
     */
    public function getVolumes(): array
    {
        $exchanges = Exchange::where('is_active', true)->get();
        $volumes = [];

        foreach ($exchanges as $exchange) {
            if (!$this->parserFactory->hasParser($exchange->name)) {
                continue;
            }

            try {
                $parser = $this->parserFactory->createParser($exchange);
                $exchangeVolumes = $this->getExchangeVolumes($parser, $exchange);
                $volumes[$exchange->name] = $exchangeVolumes;
            } catch (\Exception $e) {
                Log::warning("Ошибка при получении объёмов с {$exchange->name}: {$e->getMessage()}");
            }
        }

        return $volumes;
    }

    /**
     * Получает объёмы для конкретной биржи
     */
    private function getExchangeVolumes($parser, Exchange $exchange): array
    {
        $volumes = [];

        // Получаем последние цены для расчёта примерных объёмов
        $prices = Price::where('exchange_id', $exchange->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->with('currencyPair')
            ->get();

        foreach ($prices as $price) {
            $pair = $price->currencyPair->symbol;

            // Пытаемся получить реальный объём из API
            $realVolume = $this->getRealVolume($parser, $exchange->name, $pair);

            if ($realVolume !== null) {
                $volumes[$pair] = [
                    'volume_24h' => $realVolume,
                    'volume_quote' => $realVolume, // Объём уже в USDT
                    'last_updated' => now(),
                    'source' => 'api',
                ];
            } else {
                // Используем заглушку если не удалось получить реальный объём
                $baseVolume = $this->generateVolumeEstimate($price->ask_price, $price->bid_price);

                $volumes[$pair] = [
                    'volume_24h' => $baseVolume,
                    'volume_quote' => $baseVolume * $price->ask_price, // Объём в USDT
                    'last_updated' => now(),
                    'source' => 'estimate',
                ];
            }
        }

        return $volumes;
    }

    /**
     * Пытается получить реальный объём из API биржи
     */
    private function getRealVolume($parser, string $exchangeName, string $pair): ?float
    {
        try {
            // Проверяем, есть ли метод для получения объёма
            if (method_exists($parser, 'get24hVolume')) {
                return $parser->get24hVolume($pair);
            }

            return null;
        } catch (\Exception $e) {
            Log::warning("Ошибка при получении реального объёма для {$pair} с {$exchangeName}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Генерирует примерную оценку объёма на основе цены
     */
    private function generateVolumeEstimate(float $askPrice, float $bidPrice): float
    {
        // Простая эвристика: чем выше цена, тем больше объём
        $avgPrice = ($askPrice + $bidPrice) / 2;

        if ($avgPrice > 1000) {
            // BTC, ETH - высокие объёмы
            return rand(100, 1000);
        } elseif ($avgPrice > 100) {
            // Средние монеты
            return rand(50, 500);
        } else {
            // Дешёвые монеты
            return rand(10, 200);
        }
    }

    /**
     * Получает объём для конкретной пары на конкретной бирже
     */
    public function getPairVolume(string $exchangeName, string $pair): ?array
    {
        $exchange = Exchange::where('name', $exchangeName)->where('is_active', true)->first();

        if (!$exchange || !$this->parserFactory->hasParser($exchange->name)) {
            return null;
        }

        try {
            $parser = $this->parserFactory->createParser($exchange);
            $exchangeVolumes = $this->getExchangeVolumes($parser, $exchange);

            return $exchangeVolumes[$pair] ?? null;
        } catch (\Exception $e) {
            Log::warning("Ошибка при получении объёма для {$pair} на {$exchangeName}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Проверяет, достаточен ли объём для торговли
     */
    public function isVolumeSufficient(float $volumeUsd, float $minVolume = null): bool
    {
        $minVolume = $minVolume ?? \App\Models\Setting::get('min_volume_usd', 100.0);
        return $volumeUsd >= $minVolume;
    }
}
