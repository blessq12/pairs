<?php

namespace App\Services;

use App\Models\ArbitrageOpportunity;
use App\Models\Exchange;
use App\Models\ExchangePair;
use App\Models\Setting;
use App\Parsers\ExchangeParserFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ArbitrageAnalysisService
{
    private ExchangeParserFactory $parserFactory;

    public function __construct(ExchangeParserFactory $parserFactory)
    {
        $this->parserFactory = $parserFactory;
    }

    /**
     * Анализирует цены и находит арбитражные возможности
     */
    public function analyzeArbitrage(): array
    {
        $this->info('🔍 Начинаем анализ арбитража...');

        // Получаем только активные пары из ExchangePair
        $exchangePairs = ExchangePair::getActivePairsForArbitrage();

        if ($exchangePairs->isEmpty()) {
            $this->info('❌ Нет активных пар для анализа арбитража');
            return [];
        }

        $this->info("📊 Найдено {$exchangePairs->count()} активных пар для анализа");
        
        // Выводим детали пар
        foreach ($exchangePairs as $pair) {
            $this->info("  - {$pair->exchange->name}: {$pair->base_currency}/{$pair->quote_currency} ({$pair->symbol_on_exchange})");
        }

        // Группируем пары по символу (base_currency + quote_currency)
        $pairsBySymbol = $exchangePairs->groupBy(function ($pair) {
            return strtoupper($pair->base_currency . $pair->quote_currency);
        });

        $minProfit = Setting::get('min_profit_percent', 2.0);
        $minVolume = Setting::get('min_volume_usd', 100.0);

        $opportunities = [];
        $totalAnalyzed = 0;

        foreach ($pairsBySymbol as $symbol => $pairsForSymbol) {
            // Проверяем что пара торгуется минимум на 2 биржах
            if ($pairsForSymbol->count() < 2) {
                continue;
            }

            $this->info("🔍 Анализируем пару {$symbol} на {$pairsForSymbol->count()} биржах");

            $symbolOpportunities = $this->analyzeSymbol($pairsForSymbol, $minProfit, $minVolume);
            $opportunities = array_merge($opportunities, $symbolOpportunities);
            $totalAnalyzed++;
        }

        $this->info("📊 Проанализировано {$totalAnalyzed} пар, найдено " . count($opportunities) . " возможностей");

        return $opportunities;
    }

    /**
     * Анализирует конкретную пару на арбитражные возможности
     */
    private function analyzeSymbol(Collection $pairsForSymbol, float $minProfit, float $minVolume): array
    {
        $opportunities = [];
        $baseCurrency = $pairsForSymbol->first()->base_currency;
        $quoteCurrency = $pairsForSymbol->first()->quote_currency;

        // Группируем пары по биржам для оптимизации запросов
        $pairsByExchange = $pairsForSymbol->groupBy('exchange_id');

        // Получаем цены для всех бирж по этой паре
        $priceMatrix = [];
        foreach ($pairsByExchange as $exchangeId => $pairsForExchange) {
            $exchange = $pairsForExchange->first()->exchange;

            try {
                if (!$this->parserFactory->hasParser($exchange->name)) {
                    $this->info("⚠️  Парсер для биржи {$exchange->name} не найден, пропускаем");
                    continue;
                }

                $parser = $this->parserFactory->createParser($exchange);
                $exchangePair = $pairsForExchange->first();

                // Получаем тикер в реальном времени
                $ticker = $parser->getTicker($exchangePair->symbol_on_exchange);

                $priceMatrix[$exchangeId] = [
                    'bid' => $ticker['bid'],
                    'ask' => $ticker['ask'],
                    'exchange' => $exchange,
                    'exchange_pair' => $exchangePair,
                ];

                $this->info("✅ Получена цена для {$exchange->name}: Bid={$ticker['bid']}, Ask={$ticker['ask']}");
            } catch (\Exception $e) {
                $this->info("❌ Ошибка получения цены с {$exchange->name}: {$e->getMessage()}");
                continue;
            }
        }

        if (count($priceMatrix) < 2) {
            $this->info("❌ Недостаточно цен для арбитража по паре {$baseCurrency}{$quoteCurrency}");
            return [];
        }

        // Сравниваем все комбинации бирж
        $exchangeIds = array_keys($priceMatrix);
        for ($i = 0; $i < count($exchangeIds); $i++) {
            for ($j = $i + 1; $j < count($exchangeIds); $j++) {
                $buyExchangeId = $exchangeIds[$i];
                $sellExchangeId = $exchangeIds[$j];

                // Проверяем возможность покупки на первой, продажи на второй
                $opportunity1 = $this->calculateOpportunity(
                    $baseCurrency,
                    $quoteCurrency,
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
                    $baseCurrency,
                    $quoteCurrency,
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
        string $baseCurrency,
        string $quoteCurrency,
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

        $this->info("💰 {$baseCurrency}{$quoteCurrency}: {$buyPrice['exchange']->name} -> {$sellPrice['exchange']->name}, профит: {$profitPercent}%");

        if ($profitPercent <= 0) {
            $this->info("❌ Нет профита для {$baseCurrency}{$quoteCurrency}");
            return null; // Нет профита
        }

        // Получаем комиссии из exchange_pairs
        $buyCommission = $buyPrice['exchange_pair']->taker_fee ?? $this->getDefaultCommission($buyPrice['exchange']->name);
        $sellCommission = $sellPrice['exchange_pair']->taker_fee ?? $this->getDefaultCommission($sellPrice['exchange']->name);
        $totalCommission = $buyCommission + $sellCommission;

        // Рассчитываем чистый профит после комиссий
        $netProfitPercent = $profitPercent - ($totalCommission * 100);

        $this->info("💱 Комиссии: {$buyPrice['exchange']->name}=" . ($buyCommission * 100) . "% + {$sellPrice['exchange']->name}=" . ($sellCommission * 100) . "% = " . ($totalCommission * 100) . "%");
        $this->info("📊 Чистый профит: {$netProfitPercent}% (минимум {$minProfit}%)");

        if ($netProfitPercent < $minProfit) {
            $this->info("❌ Профит ниже минимального для {$baseCurrency}{$quoteCurrency}");
            return null; // Профит ниже минимального
        }

        // Рассчитываем профит в USD (при объёме $1000)
        $profitUsd = ($netProfitPercent / 100) * 1000;

        // Для простоты используем минимальный объём из настроек
        $volume24hBuy = $minVolume;
        $volume24hSell = $minVolume;

        $this->info("📈 Объемы: {$buyPrice['exchange']->name}=${volume24hBuy}$ {$sellPrice['exchange']->name}=${volume24hSell}$ (минимум {$minVolume}$)");

        return [
            'buy_exchange_id' => $buyExchangeId,
            'sell_exchange_id' => $sellExchangeId,
            'base_currency' => $baseCurrency,
            'quote_currency' => $quoteCurrency,
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
     * Получает комиссию биржи из настроек
     */
    private function getDefaultCommission(string $exchangeName): float
    {
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
                $this->info("💾 Сохраняем: {$opportunityData['buy_exchange_id']} -> {$opportunityData['sell_exchange_id']} для пары {$opportunityData['base_currency']}{$opportunityData['quote_currency']}");

                // Проверяем, нет ли уже такой возможности
                $existing = ArbitrageOpportunity::where([
                    'buy_exchange_id' => $opportunityData['buy_exchange_id'],
                    'sell_exchange_id' => $opportunityData['sell_exchange_id'],
                    'base_currency' => $opportunityData['base_currency'],
                    'quote_currency' => $opportunityData['quote_currency'],
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
            ->with(['buyExchange', 'sellExchange'])
            ->orderByDesc('net_profit_percent')
            ->get();
    }

    private function info(string $message): void
    {
        echo $message . PHP_EOL;
        Log::info($message);
    }
}
