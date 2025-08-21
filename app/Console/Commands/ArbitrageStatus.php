<?php

namespace App\Console\Commands;

use App\Models\ArbitrageOpportunity;
use App\Models\Exchange;
use App\Models\ExchangePair;
use App\Models\Setting;
use Illuminate\Console\Command;

class ArbitrageStatus extends Command
{
    protected $signature = 'arbitrage:status';
    protected $description = 'Показывает статус системы арбитража';

    public function handle(): void
    {
        $this->info('📊 Статус системы арбитража');
        $this->newLine();

        // Статистика бирж
        $exchanges = Exchange::where('is_active', true)->get();
        $this->info('🏦 Активные биржи:');
        foreach ($exchanges as $exchange) {
            $pairsCount = ExchangePair::where('exchange_id', $exchange->id)
                ->where('is_active', true)
                ->count();
            $this->line("  • {$exchange->name}: {$pairsCount} пар");
        }
        $this->newLine();

        // Статистика пар
        $totalPairs = ExchangePair::where('is_active', true)->count();
        $this->info("📈 Всего активных пар: {$totalPairs}");
        $this->newLine();

        // Статистика возможностей
        $todayOpportunities = ArbitrageOpportunity::whereDate('created_at', today())->count();
        $totalOpportunities = ArbitrageOpportunity::count();
        $this->info("💰 Арбитражные возможности:");
        $this->line("  • Сегодня: {$todayOpportunities}");
        $this->line("  • Всего: {$totalOpportunities}");
        $this->newLine();

        // Настройки
        $settings = Setting::first();
        if ($settings) {
            $this->info('⚙️  Настройки:');
            $this->line("  • Минимальный профит: {$settings->min_profit_percent}%");
            $this->line("  • Минимальный объем: \${$settings->min_volume_usd}");
            $this->line("  • Интервал опроса: {$settings->poll_interval_minutes} мин");
        }
        $this->newLine();

        // Проверка Telegram
        $telegramService = app(\App\Services\TelegramService::class);
        $telegramStatus = $telegramService->isConfigured() ? '✅ Настроен' : '❌ Не настроен';
        $this->info("📱 Telegram: {$telegramStatus}");

        $this->newLine();
        $this->info('💡 Для запуска анализа: php artisan arbitrage:run');
    }
}
