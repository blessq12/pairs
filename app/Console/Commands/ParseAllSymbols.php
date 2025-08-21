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
        $this->info('🚀 Начинаем парсинг торговых пар с бирж...');

        $exchanges = Exchange::where('is_active', true)->get();

        if ($this->option('exchange')) {
            $exchanges = $exchanges->where('name', $this->option('exchange'));
            if ($exchanges->isEmpty()) {
                $this->error("❌ Биржа '{$this->option('exchange')}' не найдена или неактивна");
                return;
            }
        }

        $totalSymbols = 0;
        $newSymbols = 0;
        $updatedSymbols = 0;

        foreach ($exchanges as $exchange) {
            $this->info("🔄 Парсим пары с биржи {$exchange->name}...");

            try {
                if (!$this->parserFactory->hasParser($exchange->name)) {
                    $this->warn("⚠️  Парсер для биржи {$exchange->name} не найден, пропускаем");
                    continue;
                }

                $parser = $this->parserFactory->createParser($exchange);

                // Проверяем есть ли метод getAllSymbols
                if (!method_exists($parser, 'getAllSymbols')) {
                    $this->warn("⚠️  Парсер для биржи {$exchange->name} не поддерживает получение списка пар");
                    continue;
                }

                $symbols = collect($parser->getAllSymbols());
                $this->info("📊 Найдено {$symbols->count()} пар на {$exchange->name}");

                foreach ($symbols as $symbol) {
                    $totalSymbols++;

                    // Парсим символ на base и quote валюты
                    $parsed = $this->parseSymbol($symbol);
                    if (!$parsed) {
                        $this->warn("⚠️  Не удалось распарсить символ: {$symbol}");
                        continue;
                    }

                    $currencyPair = CurrencyPair::where('symbol', $symbol)->first();

                    if (!$currencyPair) {
                        // Создаем новую пару
                        $currencyPair = CurrencyPair::create([
                            'symbol' => $symbol,
                            'base_currency' => $parsed['base'],
                            'quote_currency' => $parsed['quote'],
                            'is_active' => true,
                        ]);
                        $newSymbols++;
                        $this->line("✅ Добавлена новая пара: {$symbol}");
                    } else {
                        // Обновляем существующую пару
                        $currencyPair->update([
                            'base_currency' => $parsed['base'],
                            'quote_currency' => $parsed['quote'],
                            'is_active' => true,
                        ]);
                        $updatedSymbols++;
                        $this->line("🔄 Обновлена пара: {$symbol}");
                    }

                    // Добавляем или обновляем запись в exchange_pairs
                    ExchangePair::updateOrCreate(
                        [
                            'exchange_id' => $exchange->id,
                            'currency_pair_id' => $currencyPair->id,
                        ],
                        [
                            'symbol_on_exchange' => $symbol,
                            'is_active' => true,
                        ]
                    );
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
                ['Новых пар добавлено', $newSymbols],
                ['Пар обновлено', $updatedSymbols],
                ['Всего пар в БД', CurrencyPair::count()],
                ['Активных пар', CurrencyPair::where('is_active', true)->count()],
            ]
        );

        $this->info('✨ Парсинг торговых пар завершён!');
    }

    /**
     * Парсит символ пары на base и quote валюты
     */
    private function parseSymbol(string $symbol): ?array
    {
        // Популярные quote валюты
        $quoteCurrencies = ['USDT', 'USDC', 'BTC', 'ETH', 'BNB', 'BUSD', 'TUSD', 'DAI', 'FRAX'];

        foreach ($quoteCurrencies as $quote) {
            if (str_ends_with($symbol, $quote)) {
                $base = substr($symbol, 0, -strlen($quote));
                if (!empty($base)) {
                    return [
                        'base' => $base,
                        'quote' => $quote,
                    ];
                }
            }
        }

        // Если не нашли стандартную quote валюту, пробуем найти любую 3-4 буквенную валюту в конце
        if (preg_match('/^(.+?)([A-Z]{3,4})$/', $symbol, $matches)) {
            return [
                'base' => $matches[1],
                'quote' => $matches[2],
            ];
        }

        return null;
    }
}
