<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunArbitrageAnalysis extends Command
{
    protected $signature = 'arbitrage:run {--skip-parsing : Пропустить парсинг цен}';
    protected $description = 'Запускает полный цикл арбитража: парсинг цен + анализ + уведомления';

    public function handle(): void
    {
        $this->info('🚀 Запуск автоматического анализа арбитража...');

        $startTime = microtime(true);

        try {
            // Шаг 1: Парсинг цен (если не пропущен)
            if (!$this->option('skip-parsing')) {
                $this->info('📊 Шаг 1: Получение актуальных цен...');
                $this->call('pairs:parse-symbols');
                $this->info('✅ Цены получены');
            } else {
                $this->info('⏭️  Парсинг цен пропущен');
            }

            // Шаг 2: Анализ арбитража
            $this->info('🔍 Шаг 2: Анализ арбитражных возможностей...');
            $this->call('pairs:arbitrage-analysis');
            $this->info('✅ Анализ завершен');

            $executionTime = round(microtime(true) - $startTime, 2);
            $this->info("✨ Полный цикл арбитража завершен за {$executionTime} сек");

            Log::info('Автоматический анализ арбитража завершен успешно', [
                'execution_time' => $executionTime,
                'skip_parsing' => $this->option('skip-parsing')
            ]);
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при выполнении автоматического анализа: {$e->getMessage()}");
            Log::error('Ошибка автоматического анализа арбитража', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Отправляем уведомление об ошибке в Telegram
            try {
                $telegramService = app(\App\Services\TelegramService::class);
                if ($telegramService->isConfigured()) {
                    $telegramService->sendErrorMessage("Ошибка автоматического анализа: {$e->getMessage()}");
                }
            } catch (\Exception $telegramError) {
                Log::error('Не удалось отправить уведомление об ошибке в Telegram', [
                    'error' => $telegramError->getMessage()
                ]);
            }
        }
    }
}
