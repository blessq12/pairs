<?php

namespace App\Console\Commands;

use App\Jobs\UpdatePricesChunk;
use App\Models\ExchangePair;
use Illuminate\Console\Command;

class PollExchangesQueued extends Command
{
    protected $signature = 'pairs:poll-queued {--chunk-size=20 : Размер чанка для обработки}';
    protected $description = 'Обновляет цены через очереди с разбивкой на чанки (только активные пары для арбитража)';

    public function handle(): void
    {
        $chunkSize = (int)$this->option('chunk-size');
        
        $this->info("🚀 Запускаем обновление цен через очереди (чанк: {$chunkSize} пар)");

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
        $this->info("🔄 Создаем {$totalChunks} джоб для обновления цен");

        $dispatchedCount = 0;
        foreach ($pairChunks as $chunk) {
            $exchangePairIds = $chunk->pluck('id')->toArray();
            
            UpdatePricesChunk::dispatch($exchangePairIds);
            $dispatchedCount++;
            
            $this->info("✅ Отправлена джоба #{$dispatchedCount} для " . count($exchangePairIds) . " пар");
        }

        $this->info("🎯 Отправлено {$dispatchedCount} джоб в очередь");
        $this->info("💡 Запустите: php artisan queue:work --timeout=180");
    }
}
