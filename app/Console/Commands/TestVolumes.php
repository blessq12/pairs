<?php

namespace App\Console\Commands;

use App\Models\Exchange;
use App\Services\VolumeAnalysisService;
use Illuminate\Console\Command;

class TestVolumes extends Command
{
    protected $signature = 'pairs:test-volumes {--exchange= : Конкретная биржа для тестирования}';
    protected $description = 'Тестирование получения объёмов торгов';

    private VolumeAnalysisService $volumeService;

    public function __construct(VolumeAnalysisService $volumeService)
    {
        parent::__construct();
        $this->volumeService = $volumeService;
    }

    public function handle(): void
    {
        $this->info('📊 Тестирование получения объёмов торгов...');

        $exchangeName = $this->option('exchange');

        if ($exchangeName) {
            $this->testSingleExchange($exchangeName);
        } else {
            $this->testAllExchanges();
        }
    }

    private function testSingleExchange(string $exchangeName): void
    {
        $this->info("🔍 Тестируем биржу: {$exchangeName}");

        $pairs = ['BTCUSDT', 'ETHUSDT'];

        foreach ($pairs as $pair) {
            $volumeData = $this->volumeService->getPairVolume($exchangeName, $pair);

            if ($volumeData) {
                $this->info("✅ {$pair}: {$volumeData['volume_quote']} USDT (источник: {$volumeData['source']})");
            } else {
                $this->warn("⚠️  {$pair}: не удалось получить объём");
            }
        }
    }

    private function testAllExchanges(): void
    {
        $exchanges = Exchange::where('is_active', true)->get();

        foreach ($exchanges as $exchange) {
            $this->info("🔍 Тестируем биржу: {$exchange->name}");

            $volumes = $this->volumeService->getVolumes();
            $exchangeVolumes = $volumes[$exchange->name] ?? [];

            if (empty($exchangeVolumes)) {
                $this->warn("⚠️  Нет данных об объёмах");
                continue;
            }

            foreach ($exchangeVolumes as $pair => $volumeData) {
                $this->info("  • {$pair}: {$volumeData['volume_quote']} USDT (источник: {$volumeData['source']})");
            }

            $this->newLine();
        }
    }
}
