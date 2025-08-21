<?php

namespace App\Console\Commands;

use App\Models\CurrencyPair;
use App\Models\Exchange;
use App\Models\ExchangePair;
use App\Parsers\ExchangeParserFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ParseAllSymbols extends Command
{
    protected $signature = 'pairs:parse-symbols {--exchange= : Парсить только указанную биржу}';
    protected $description = 'Парсит все доступные торговые пары с бирж и сохраняет их в базу данных';

    private ExchangeParserFactory $parserFactory;

    public function __construct(ExchangeParserFactory $parserFactory)
    {
        parent::__construct();
        $this->parserFactory = $parserFactory;
    }

    public function handle(): void
    {
        $this->info('🚀 Начинаем парсинг торговых пар с бирж (только активные пары из ExchangePair)...');

        // Получаем только активные пары из ExchangePair
        $exchangePairs = ExchangePair::getActivePairsForArbitrage();

        if ($exchangePairs->isEmpty()) {
            $this->error("❌ Не найдено активных пар для парсинга. Сначала добавьте пары в ExchangePair!");
            return;
        }

        $this->info("📊 Найдено {$exchangePairs->count()} активных пар для парсинга");

        $totalSymbols = 0;
        $successfulSymbols = 0;
        $failedSymbols = 0;

        // Группируем пары по биржам для оптимизации
        $pairsByExchange = $exchangePairs->groupBy('exchange_id');

        foreach ($pairsByExchange as $exchangeId => $pairs) {
            $exchange = Exchange::find($exchangeId);
            if (!$exchange || !$exchange->is_active) {
                $this->warn("⚠️  Биржа с ID {$exchangeId} не найдена или неактивна, пропускаем");
                continue;
            }

            $this->info("🔄 Парсим пары с биржи {$exchange->name}...");

            try {
                if (!$this->parserFactory->hasParser($exchange->name)) {
                    $this->warn("⚠️  Парсер для биржи {$exchange->name} не найден, пропускаем");
                    continue;
                }

                $parser = $this->parserFactory->createParser($exchange);

                foreach ($pairs as $exchangePair) {
                    $totalSymbols++;

                    try {
                        // Получаем тикер для конкретной пары
                        $ticker = $parser->getTicker($exchangePair->symbol_on_exchange);

                        // Здесь можно добавить логику сохранения цен
                        // Например, сохранить в таблицу prices

                        $successfulSymbols++;
                        $this->line("✅ Обработана пара: {$exchangePair->symbol_on_exchange} (Ask: {$ticker['ask']}, Bid: {$ticker['bid']})");
                    } catch (\Exception $e) {
                        $failedSymbols++;
                        $this->warn("⚠️  Ошибка при обработке пары {$exchangePair->symbol_on_exchange}: {$e->getMessage()}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("❌ Ошибка при парсинге биржи {$exchange->name}: {$e->getMessage()}");
                Log::error("Ошибка при парсинге биржи {$exchange->name}", [
                    'exchange' => $exchange->name,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('📈 Статистика парсинга:');
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего обработано пар', $totalSymbols],
                ['Успешно обработано', $successfulSymbols],
                ['Ошибок обработки', $failedSymbols],
                ['Всего пар в ExchangePair', ExchangePair::count()],
                ['Активных пар в ExchangePair', ExchangePair::where('is_active', true)->count()],
            ]
        );

        $this->info('✨ Парсинг торговых пар завершён!');
    }
}
