<?php

namespace App\Services;

use App\Models\ArbitrageOpportunity;
use App\Models\CurrencyPair;
use App\Models\Exchange;
use App\Models\ExchangePair;
use App\Models\Price;
use App\Models\Setting;
use App\Services\VolumeAnalysisService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ArbitrageAnalysisService
{
    private VolumeAnalysisService $volumeService;

    public function __construct(VolumeAnalysisService $volumeService)
    {
        $this->volumeService = $volumeService;
    }

    /**
     * Анализирует цены и находит арбитражные возможности
     */
    public function analyzeArbitrage(): array
    {
        $this->info('🔍 Начинаем анализ арбитража...');

        // Получаем только те пары, которые торгуются на активных биржах
        $exchangePairs = ExchangePair::getAllActive();

        if ($exchangePairs->isEmpty()) {
            $this->info('❌ Нет активных пар на биржах для анализа');
            return [];
        }

        // Группируем по парам
        $pairsByCurrency = $exchangePairs->groupBy('currency_pair_id');

        $minProfit = Setting::get('min_profit_percent', 2.0);
        $minVolume = Setting::get('min_volume_usd', 100.0);

        $opportunities = [];
        $totalAnalyzed = 0;

        foreach ($pairsByCurrency as $pairId => $exchangePairsForPair) {
            // Проверяем что пара торгуется минимум на 2 биржах
            if ($exchangePairsForPair->count() < 2) {
                continue;
            }

            $pair = $exchangePairsForPair->first()->currencyPair;
            $exchanges = $exchangePairsForPair->pluck('exchange');

            $pairOpportunities = $this->analyzePair($pair, $exchanges, $minProfit, $minVolume);
            $opportunities = array_merge($opportunities, $pairOpportunities);
            $totalAnalyzed++;
        }

        $this->info("📊 Проанализировано {$totalAnalyzed} пар, найдено " . count($opportunities) . " возможностей");

        return $opportunities;
    }

    /**
     * Анализирует конкретную пару на арбитражные возможности
     */
    public function analyzePair(CurrencyPair $pair, Collection $exchanges, float $minProfit = null, float $minVolume = null): array
    {
        // Устанавливаем значения по умолчанию если не переданы
        if ($minProfit === null) {
            $minProfit = Setting::get('min_profit_percent', 2.0);
        }
        if ($minVolume === null) {
            $minVolume = Setting::get('min_volume_usd', 100.0);
        }

        $opportunities = [];

        // Получаем информацию о том, как эта пара торгуется на каждой бирже
        $exchangePairs = ExchangePair::where('currency_pair_id', $pair->id)
            ->whereIn('exchange_id', $exchanges->pluck('id'))
            ->where('is_active', true)
            ->with('exchange')
            ->get();

        if ($exchangePairs->count() < 2) {
            $this->info("❌ Пара {$pair->symbol} торгуется менее чем на 2 биржах");
            return [];
        }

        // Получаем последние цены для всех бирж по этой паре
        $prices = Price::where('currency_pair_id', $pair->id)
            ->whereIn('exchange_id', $exchanges->pluck('id'))
            ->where('created_at', '>=', now()->subMinutes(5)) // Только свежие цены
            ->with('exchange')
            ->get()
            ->groupBy('exchange_id');

        $this->info("🔍 Анализируем пару {$pair->symbol}: найдено цен для {$prices->count()} бирж");

        if ($prices->count() < 2) {
            $this->info("❌ Недостаточно свежих цен для арбитража по паре {$pair->symbol}");
            return []; // Нужно минимум 2 биржи для арбитража
        }

        // Создаём матрицу цен для сравнения
        $priceMatrix = [];
        foreach ($prices as $exchangeId => $exchangePrices) {
            $latestPrice = $exchangePrices->sortByDesc('created_at')->first();
            if ($latestPrice) {
                $exchangePair = $exchangePairs->where('exchange_id', $exchangeId)->first();
                $priceMatrix[$exchangeId] = [
                    'bid' => $latestPrice->bid_price,
                    'ask' => $latestPrice->ask_price,
                    'exchange' => $latestPrice->exchange,
                    'symbol_on_exchange' => $exchangePair ? $exchangePair->symbol_on_exchange : $pair->symbol,
                ];
            }
        }

        // Сравниваем все комбинации бирж
        $exchangeIds = array_keys($priceMatrix);
        for ($i = 0; $i < count($exchangeIds); $i++) {
            for ($j = $i + 1; $j < count($exchangeIds); $j++) {
                $buyExchangeId = $exchangeIds[$i];
                $sellExchangeId = $exchangeIds[$j];

                // Проверяем возможность покупки на первой, продажи на второй
                $opportunity1 = $this->calculateOpportunity(
                    $pair,
                    $priceMatrix[$buyExchangeId],
                    $priceMatrix[$sellExchangeId],
                    $buyExchangeId,
                    $sellExchangeId,
                    $minProfit,
                    $minVolume
                );

                if ($opportunity1) {
                    $opportunities[] = $opportunity1;
                }

                // Проверяем обратную возможность
                $opportunity2 = $this->calculateOpportunity(
                    $pair,
                    $priceMatrix[$sellExchangeId],
                    $priceMatrix[$buyExchangeId],
                    $sellExchangeId,
                    $buyExchangeId,
                    $minProfit,
                    $minVolume
                );

                if ($opportunity2) {
                    $opportunities[] = $opportunity2;
                }
            }
        }

        return $opportunities;
    }

    /**
     * Рассчитывает арбитражную возможность между двумя биржами
     */
    private function calculateOpportunity(
        CurrencyPair $pair,
        array $buyPrice,
        array $sellPrice,
        int $buyExchangeId,
        int $sellExchangeId,
        float $minProfit,
        float $minVolume
    ): ?array {
        $buyPriceValue = $buyPrice['ask']; // Покупаем по ask
        $sellPriceValue = $sellPrice['bid']; // Продаём по bid

        // Рассчитываем базовый профит
        $profitPercent = (($sellPriceValue - $buyPriceValue) / $buyPriceValue) * 100;

        $this->info("💰 {$pair->symbol}: {$buyPrice['exchange']->name} -> {$sellPrice['exchange']->name}, профит: {$profitPercent}%");

        if ($profitPercent <= 0) {
            $this->info("❌ Нет профита для {$pair->symbol}");
            return null; // Нет профита
        }

        // Получаем комиссии бирж из exchange_pairs
        $buyCommission = $this->getExchangeCommission($buyPrice['exchange']->name, $pair->id);
        $sellCommission = $this->getExchangeCommission($sellPrice['exchange']->name, $pair->id);
        $totalCommission = $buyCommission + $sellCommission;

        // Рассчитываем чистый профит после комиссий
        $netProfitPercent = $profitPercent - ($totalCommission * 100);

        $this->info("💱 Комиссии: {$buyPrice['exchange']->name}=" . ($buyCommission * 100) . "% + {$sellPrice['exchange']->name}=" . ($sellCommission * 100) . "% = " . ($totalCommission * 100) . "%");
        $this->info("📊 Чистый профит: {$netProfitPercent}% (минимум {$minProfit}%)");

        if ($netProfitPercent < $minProfit) {
            $this->info("❌ Профит ниже минимального для {$pair->symbol}");
            return null; // Профит ниже минимального
        }

        // Рассчитываем профит в USD (при объёме $1000)
        $profitUsd = ($netProfitPercent / 100) * 1000;

        // Получаем реальные объёмы торгов
        $volume24hBuy = $this->getVolumeForExchange($buyPrice['exchange']->name, $pair->symbol);
        $volume24hSell = $this->getVolumeForExchange($sellPrice['exchange']->name, $pair->symbol);

        $this->info("📈 Объемы: {$buyPrice['exchange']->name}=${volume24hBuy}$ {$sellPrice['exchange']->name}=${volume24hSell}$ (минимум {$minVolume}$)");

        if (
            !$this->volumeService->isVolumeSufficient($volume24hBuy, $minVolume) ||
            !$this->volumeService->isVolumeSufficient($volume24hSell, $minVolume)
        ) {
            $this->info("❌ Объём недостаточный для {$pair->symbol}");
            return null; // Объём недостаточный
        }

        return [
            'buy_exchange_id' => $buyExchangeId,
            'sell_exchange_id' => $sellExchangeId,
            'currency_pair_id' => $pair->id,
            'buy_price' => $buyPriceValue,
            'sell_price' => $sellPriceValue,
            'profit_percent' => $profitPercent,
            'profit_usd' => $profitUsd,
            'volume_24h_buy' => $volume24hBuy,
            'volume_24h_sell' => $volume24hSell,
            'min_volume_usd' => $minVolume,
            'buy_commission' => $buyCommission,
            'sell_commission' => $sellCommission,
            'total_commission' => $totalCommission,
            'net_profit_percent' => $netProfitPercent,
            'is_active' => true,
            'detected_at' => now(),
        ];
    }

    /**
     * Получает комиссию биржи из exchange_pairs или настроек
     */
    private function getExchangeCommission(string $exchangeName, int $currencyPairId): float
    {
        // Сначала пытаемся получить комиссию из exchange_pairs
        $exchangePair = ExchangePair::where('exchange_id', function($query) use ($exchangeName) {
                $query->select('id')->from('exchanges')->where('name', $exchangeName);
            })
            ->where('currency_pair_id', $currencyPairId)
            ->where('is_active', true)
            ->first();

        if ($exchangePair && $exchangePair->taker_fee !== null) {
            return $exchangePair->taker_fee;
        }

        // Если нет в exchange_pairs, берем из настроек
        $commissionKey = strtolower($exchangeName) . '_commission';
        return Setting::get($commissionKey, 0.001); // По умолчанию 0.1%
    }

    /**
     * Сохраняет найденные возможности в базу данных
     */
    public function saveOpportunities(array $opportunities): int
    {
        if (empty($opportunities)) {
            return 0;
        }

        $this->info("💾 Пытаемся сохранить " . count($opportunities) . " возможностей");

        $saved = 0;
        foreach ($opportunities as $opportunityData) {
            try {
                $this->info("💾 Сохраняем: {$opportunityData['buy_exchange_id']} -> {$opportunityData['sell_exchange_id']} для пары {$opportunityData['currency_pair_id']}");

                // Проверяем, нет ли уже такой возможности
                $existing = ArbitrageOpportunity::where([
                    'buy_exchange_id' => $opportunityData['buy_exchange_id'],
                    'sell_exchange_id' => $opportunityData['sell_exchange_id'],
                    'currency_pair_id' => $opportunityData['currency_pair_id'],
                ])
                    ->where('is_active', true)
                    ->first();

                if ($existing) {
                    // Обновляем существующую возможность
                    $existing->update($opportunityData);
                    $this->info("✅ Обновлена существующая возможность");
                } else {
                    // Создаём новую возможность
                    ArbitrageOpportunity::create($opportunityData);
                    $this->info("✅ Создана новая возможность");
                }

                $saved++;
            } catch (\Exception $e) {
                $this->info("❌ Ошибка при сохранении: " . $e->getMessage());
                Log::error('Ошибка при сохранении арбитражной возможности', [
                    'data' => $opportunityData,
                    'exception' => $e
                ]);
            }
        }

        return $saved;
    }

    /**
     * Получает возможности готовые для алерта
     */
    public function getOpportunitiesForAlert(): Collection
    {
        return ArbitrageOpportunity::active()
            ->profitable()
            ->withVolume()
            ->readyForAlert()
            ->with(['buyExchange', 'sellExchange', 'currencyPair'])
            ->orderByDesc('net_profit_percent')
            ->get();
    }

    /**
     * Получает объём для конкретной биржи и пары
     */
    private function getVolumeForExchange(string $exchangeName, string $pair): float
    {
        $volumeData = $this->volumeService->getPairVolume($exchangeName, $pair);

        if ($volumeData && isset($volumeData['volume_quote'])) {
            return $volumeData['volume_quote'];
        }

        // Возвращаем минимальный объём если не удалось получить данные
        return Setting::get('min_volume_usd', 100.0);
    }

    private function info(string $message): void
    {
        // В будущем можно добавить логирование или вывод в консоль
        Log::info($message);
    }
}
