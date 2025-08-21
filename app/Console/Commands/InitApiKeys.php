<?php

namespace App\Console\Commands;

use App\Models\Exchange;
use App\Models\ExchangeApiKey;
use Illuminate\Console\Command;

class InitApiKeys extends Command
{
    protected $signature = 'pairs:init-api-keys';
    protected $description = 'Инициализация API ключей для всех бирж';

    public function handle(): void
    {
        $this->info('🔑 Инициализация API ключей...');

        // API ключи для бирж (будут автоматически зашифрованы)
        $apiKeys = [
            [
                'exchange_name' => 'Bybit',
                'api_key' => 'your_bybit_api_key_here',
                'api_secret' => 'your_bybit_api_secret_here',
            ],
            [
                'exchange_name' => 'MEXC',
                'api_key' => 'your_mexc_api_key_here',
                'api_secret' => 'your_mexc_api_secret_here',
            ],
            [
                'exchange_name' => 'CoinEx',
                'api_key' => 'your_coinex_api_key_here',
                'api_secret' => 'your_coinex_api_secret_here',
            ],
        ];

        foreach ($apiKeys as $apiKeyData) {
            $exchange = Exchange::where('name', $apiKeyData['exchange_name'])->first();

            if (!$exchange) {
                $this->warn("⚠️  Биржа {$apiKeyData['exchange_name']} не найдена");
                continue;
            }

            // Создаём API ключ (автоматически зашифруется)
            $apiKey = ExchangeApiKey::create([
                'exchange_id' => $exchange->id,
                'api_key' => $apiKeyData['api_key'],
                'api_secret' => $apiKeyData['api_secret'],
            ]);

            $this->info("✅ Добавлен API ключ для {$apiKeyData['exchange_name']}");
        }

        $this->info('✨ Инициализация API ключей завершена!');

        // Выводим статистику
        $this->newLine();
        $this->table(
            ['Биржа', 'API Ключ', 'Статус'],
            ExchangeApiKey::with('exchange')->get()->map(fn($apiKey) => [
                $apiKey->exchange->name,
                '••••' . substr($apiKey->api_key, -4),
                '✅ Настроен'
            ])
        );
    }
}
