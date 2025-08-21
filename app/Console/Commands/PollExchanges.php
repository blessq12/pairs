<?php

namespace App\Console\Commands;

use App\Models\CurrencyPair;
use App\Models\Exchange;
use App\Models\Price;
use App\Models\Setting;
use App\Parsers\ExchangeParserFactory;
use App\Parsers\ExchangeParserInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PollExchanges extends Command
{
    protected $signature = 'pairs:poll-exchanges {--pairs= : Список пар через запятую (например: BTC/USDT,ETH/USDT)}';
    protected $description = 'Опрос всех активных бирж для получения текущих цен';

    private ExchangeParserFactory $parserFactory;

    public function __construct(ExchangeParserFactory $parserFactory)
    {
        parent::__construct();
        $this->parserFactory = $parserFactory;
    }

    public function handle(): void
    {
        $this->info('🚀 Начинаем опрос бирж...');

        $exchanges = Exchange::where('is_active', true)->get();
        $pairs = $this->getPairsToPoll();

        if ($exchanges->isEmpty()) {
            $this->error('❌ Нет активных бирж для опроса');
            return;
        }

        if ($pairs->isEmpty()) {
            $this->error('❌ Нет активных валютных пар для опроса');
            return;
        }

        $this->info("📊 Опрашиваем {$exchanges->count()} бирж по {$pairs->count()} парам");

        $totalRequests = 0;
        $successfulRequests = 0;
        $errors = [];

        foreach ($exchanges as $exchange) {
            if (!$this->parserFactory->hasParser($exchange->name)) {
                $this->warn("⚠️  Парсер для биржи {$exchange->name} не найден");
                continue;
            }

            $this->info("🔄 Опрашиваем {$exchange->name}...");

            try {
                $parser = $this->parserFactory->createParser($exchange);
                $exchangeResults = $this->pollExchange($parser, $exchange, $pairs);

                $totalRequests += $exchangeResults['total'];
                $successfulRequests += $exchangeResults['successful'];
                $errors = array_merge($errors, $exchangeResults['errors']);

                $this->info("✅ {$exchange->name}: {$exchangeResults['successful']}/{$exchangeResults['total']} успешно");
            } catch (\Exception $e) {
                $error = "Ошибка при опросе {$exchange->name}: {$e->getMessage()}";
                $errors[] = $error;
                $this->error("❌ {$error}");
                Log::error($error, ['exchange' => $exchange->name, 'exception' => $e]);
            }
        }

        // Выводим итоговую статистику
        $this->newLine();
        $this->info("📈 Итоговая статистика:");
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Всего запросов', $totalRequests],
                ['Успешных', $successfulRequests],
                ['Ошибок', count($errors)],
                ['Процент успеха', $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 1) . '%' : '0%'],
            ]
        );

        if (!empty($errors)) {
            $this->newLine();
            $this->warn('⚠️  Ошибки:');
            foreach (array_slice($errors, 0, 5) as $error) {
                $this->line("  • {$error}");
            }
            if (count($errors) > 5) {
                $this->line("  • ... и ещё " . (count($errors) - 5) . " ошибок");
            }
        }

        $this->info('✨ Опрос бирж завершён!');
    }

    private function getPairsToPoll()
    {
        $pairsInput = $this->option('pairs');

        if ($pairsInput) {
            $pairSymbols = array_map('trim', explode(',', $pairsInput));
            return CurrencyPair::whereIn('symbol', $pairSymbols)
                ->where('is_active', true)
                ->get();
        }

        return CurrencyPair::where('is_active', true)->get();
    }

    private function pollExchange(ExchangeParserInterface $parser, Exchange $exchange, $pairs): array
    {
        $total = 0;
        $successful = 0;
        $errors = [];

        foreach ($pairs as $pair) {
            $total++;

            try {
                $ticker = $parser->getTicker($pair->symbol);

                // Сохраняем цену в базу
                Price::updateOrCreate(
                    [
                        'exchange_id' => $exchange->id,
                        'currency_pair_id' => $pair->id,
                        'created_at' => now()->startOfMinute(), // Группируем по минутам
                    ],
                    [
                        'bid_price' => $ticker['bid'],
                        'ask_price' => $ticker['ask'],
                    ]
                );

                $successful++;
            } catch (\Exception $e) {
                $error = "Ошибка при получении цены {$pair->symbol} с {$exchange->name}: {$e->getMessage()}";
                $errors[] = $error;
                Log::warning($error, [
                    'exchange' => $exchange->name,
                    'pair' => $pair->symbol,
                    'exception' => $e
                ]);
            }
        }

        return [
            'total' => $total,
            'successful' => $successful,
            'errors' => $errors,
        ];
    }
}
