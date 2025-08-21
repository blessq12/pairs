<?php

namespace App\Console\Commands;

use App\Jobs\ProcessArbitrageChunk;
use App\Models\ExchangePair;
use Illuminate\Console\Command;

class ArbitrageAnalysisQueued extends Command
{
    protected $signature = 'pairs:arbitrage-queued {--chunk-size=50 : Размер чанка для обработки}';
    protected $description = 'Запускает анализ арбитража через очереди с разбивкой на чанки (только активные пары)';

    public function handle(): void
    {
        $chunkSize = (int)$this->option('chunk-size');
        
        $this->info("🚀 Запускаем анализ арбитража через очереди (чанк: {$chunkSize} пар)");

        // Получаем только активные пары для арбитража
        $exchangePairs = ExchangePair::getActiveForArbitrage();

        $this->info("📊 Всего активных пар для арбитража: {$exchangePairs->count()}");

        if ($exchangePairs->isEmpty()) {
            $this->error('❌ Нет активных пар для арбитража');
            return;
        }

        // Разбиваем exchange_pairs на чанки
        $pairChunks = $exchangePairs->chunk($chunkSize);

        $totalChunks = $pairChunks->count();
        $this->info("🔄 Создаем {$totalChunks} джоб для обработки");

        $dispatchedCount = 0;
        foreach ($pairChunks as $chunk) {
            $exchangePairIds = $chunk->pluck('id')->toArray();
            
            ProcessArbitrageChunk::dispatch($exchangePairIds);
            $dispatchedCount++;
            
            $this->info("✅ Отправлена джоба #{$dispatchedCount} для " . count($exchangePairIds) . " пар");
        }

        $this->info("🎯 Отправлено {$dispatchedCount} джоб в очередь");
        $this->info("💡 Запустите: php artisan queue:work --timeout=300");
    }
}
