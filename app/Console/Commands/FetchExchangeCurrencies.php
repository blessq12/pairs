<?php

namespace App\Console\Commands;

use App\Models\Exchange;
use App\Models\ExchangeCurrency;
use App\Models\ExchangeApiKey;
use App\Parsers\ExchangeParserFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchExchangeCurrencies extends Command
{
    protected $signature = 'exchange:fetch-currencies {exchange? : ID биржи (если не указан, обрабатываются все активные)}';
    protected $description = 'Получает валюты с бирж и сохраняет их в базу данных';

    public function handle()
    {
        $exchangeId = $this->argument('exchange');

        if ($exchangeId) {
            $exchanges = Exchange::where('id', $exchangeId)->where('is_active', true)->get();
        } else {
            $exchanges = Exchange::where('is_active', true)->get();
        }

        if ($exchanges->isEmpty()) {
            $this->error('Не найдено активных бирж для обработки');
            return 1;
        }

        $this->info("Начинаем получение валют с {$exchanges->count()} бирж...");

        foreach ($exchanges as $exchange) {
            $this->processExchange($exchange);
        }

        $this->info('Обработка завершена!');
        return 0;
    }

    private function processExchange(Exchange $exchange): void
    {
        $this->info("Обрабатываем биржу: {$exchange->name}");

        try {
            // Получаем API ключи для биржи
            $apiKey = ExchangeApiKey::where('exchange_id', $exchange->id)
                ->first();

            if (!$apiKey) {
                $this->warn("Для биржи {$exchange->name} не найдены активные API ключи");
                return;
            }

            // Создаем парсер
            $factory = new ExchangeParserFactory();
            $parser = $factory->createParser($exchange);

            // Получаем валюты с биржи
            $currencies = $parser->getAllCurrencies();

            if (empty($currencies)) {
                $this->warn("Не удалось получить валюты с биржи {$exchange->name}");
                return;
            }

            $this->info("Получено " . count($currencies) . " валют с биржи {$exchange->name}");

            // Сохраняем валюты в базу
            $savedCount = 0;
            foreach ($currencies as $currencySymbol) {
                $currency = ExchangeCurrency::firstOrCreate(
                    [
                        'exchange_id' => $exchange->id,
                        'currency_symbol' => strtoupper($currencySymbol),
                    ],
                    [
                        'is_active' => true,
                    ]
                );

                if ($currency->wasRecentlyCreated) {
                    $savedCount++;
                }
            }

            $this->info("Сохранено {$savedCount} новых валют для биржи {$exchange->name}");
        } catch (\Exception $e) {
            $this->error("Ошибка при обработке биржи {$exchange->name}: {$e->getMessage()}");
            Log::error("Failed to fetch currencies for exchange {$exchange->name}", [
                'exchange_id' => $exchange->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
