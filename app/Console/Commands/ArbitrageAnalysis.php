<?php

namespace App\Console\Commands;

use App\Services\ArbitrageAnalysisService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ArbitrageAnalysis extends Command
{
    protected $signature = 'pairs:arbitrage-analysis {--test : Отправить тестовое сообщение}';
    protected $description = 'Полный цикл анализа арбитража: сбор данных, анализ, уведомления';

    private ArbitrageAnalysisService $analysisService;
    private NotificationService $notificationService;

    public function __construct(
        ArbitrageAnalysisService $analysisService,
        NotificationService $notificationService
    ) {
        parent::__construct();
        $this->analysisService = $analysisService;
        $this->notificationService = $notificationService;
    }

    public function handle(): void
    {
        $this->info('🚀 Запуск анализа арбитража...');

        // Проверяем тестовый режим
        if ($this->option('test')) {
            $this->sendTestMessage();
            return;
        }

        try {
            // 1. Анализируем арбитражные возможности
            $this->info('🔍 Анализируем арбитражные возможности...');
            $opportunities = $this->analysisService->analyzeArbitrage();

            if (empty($opportunities)) {
                $this->info('📊 Арбитражных возможностей не найдено');
                return;
            }

            $this->info("📊 Найдено " . count($opportunities) . " арбитражных возможностей");

            // 2. Сохраняем возможности в базу
            $this->info('💾 Сохраняем возможности в базу данных...');
            $saved = $this->analysisService->saveOpportunities($opportunities);
            $this->info("✅ Сохранено {$saved} возможностей");

            // 3. Получаем возможности для алерта
            $this->info('🔔 Проверяем возможности для алерта...');
            $alertOpportunities = $this->analysisService->getOpportunitiesForAlert();

            if ($alertOpportunities->isEmpty()) {
                $this->info('📢 Нет новых возможностей для алерта');
                return;
            }

            $this->info("📢 Найдено {$alertOpportunities->count()} возможностей для алерта");

            // 4. Отправляем алерты
            $this->info('📤 Отправляем алерты...');
            $sentCount = $this->notificationService->sendArbitrageAlerts($alertOpportunities);

            $this->info("✅ Отправлено {$sentCount} алертов");

            // Выводим статистику
            $this->newLine();
            $this->info('📈 Статистика анализа:');
            $this->table(
                ['Метрика', 'Значение'],
                [
                    ['Проанализировано возможностей', count($opportunities)],
                    ['Сохранено в БД', $saved],
                    ['Готово для алерта', $alertOpportunities->count()],
                    ['Отправлено алертов', $sentCount],
                ]
            );
        } catch (\Exception $e) {
            $error = "Ошибка при анализе арбитража: {$e->getMessage()}";
            $this->error("❌ {$error}");
            Log::error($error, ['exception' => $e]);

            // Отправляем сообщение об ошибке
            $this->notificationService->sendErrorMessage($error);
        }

        $this->info('✨ Анализ арбитража завершён!');
    }

    private function sendTestMessage(): void
    {
        $this->info('🧪 Отправляем тестовое сообщение...');

        $success = $this->notificationService->sendTestMessage();

        if ($success) {
            $this->info('✅ Тестовое сообщение отправлено успешно!');
        } else {
            $this->error('❌ Не удалось отправить тестовое сообщение');
        }
    }
}
