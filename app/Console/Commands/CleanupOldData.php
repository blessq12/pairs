<?php

namespace App\Console\Commands;

use App\Models\ArbitrageOpportunity;
use App\Models\Price;
use App\Models\Setting;
use Illuminate\Console\Command;

class CleanupOldData extends Command
{
    protected $signature = 'pairs:cleanup-old-data';
    protected $description = 'Очистка старых данных: цен и арбитражных возможностей';

    public function handle(): void
    {
        $this->info('🧹 Начинаем очистку старых данных...');

        // Очистка старых цен
        $this->cleanupOldPrices();

        // Очистка старых арбитражных возможностей
        $this->cleanupOldArbitrageOpportunities();

        $this->info('✨ Очистка завершена!');
    }

    private function cleanupOldPrices(): void
    {
        if (!Setting::get('price_cleanup_enabled', true)) {
            $this->info('📊 Очистка цен отключена в настройках');
            return;
        }

        $daysToKeep = Setting::get('price_history_days', 90);
        $cutoffDate = now()->subDays($daysToKeep);

        $this->info("🗑️  Удаляем цены старше {$daysToKeep} дней...");

        $deletedCount = Price::where('created_at', '<', $cutoffDate)->delete();

        $this->info("✅ Удалено {$deletedCount} записей цен");
    }

    private function cleanupOldArbitrageOpportunities(): void
    {
        $this->info('🗑️  Очищаем старые арбитражные возможности...');

        // Деактивируем возможности старше 24 часов
        $oldOpportunities = ArbitrageOpportunity::where('detected_at', '<', now()->subDay())
            ->where('is_active', true)
            ->get();

        $deactivatedCount = 0;
        foreach ($oldOpportunities as $opportunity) {
            $opportunity->deactivate();
            $deactivatedCount++;
        }

        // Удаляем возможности старше 7 дней
        $deletedCount = ArbitrageOpportunity::where('detected_at', '<', now()->subWeek())->delete();

        $this->info("✅ Деактивировано {$deactivatedCount} возможностей");
        $this->info("✅ Удалено {$deletedCount} старых записей");
    }
}
