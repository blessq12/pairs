<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class InitArbitrageSettings extends Command
{
    protected $signature = 'pairs:init-arbitrage-settings';
    protected $description = 'Инициализация настроек арбитража';

    public function handle(): void
    {
        $this->info('🚀 Инициализация настроек арбитража...');

        $settings = Setting::firstOrCreate();

        $arbitrageSettings = [
            'min_profit_percent' => 2.0,
            'min_volume_usd' => 100.0,
            'alert_cooldown_minutes' => 10,
            'poll_interval_minutes' => 5,
            'mexc_commission' => 0.001, // 0.1%
            'bybit_commission' => 0.001, // 0.1%
            'bingx_commission' => 0.001, // 0.1%
            'coinex_commission' => 0.001, // 0.1%
        ];

        foreach ($arbitrageSettings as $key => $value) {
            if (!isset($settings->$key)) {
                $settings->$key = $value;
                $this->info("✅ Добавлена настройка: {$key} = {$value}");
            }
        }

        $settings->save();
        Setting::flushCache();

        $this->info('✨ Настройки арбитража успешно инициализированы!');

        $this->newLine();
        $this->table(
            ['Настройка', 'Значение', 'Описание'],
            [
                ['min_profit_percent', '2.0%', 'Минимальный профит для уведомления'],
                ['min_volume_usd', '$100', 'Минимальный объём торгов'],
                ['alert_cooldown_minutes', '10 мин', 'Задержка между алертами'],
                ['poll_interval_minutes', '5 мин', 'Интервал опроса бирж'],
                ['mexc_commission', '0.1%', 'Комиссия MEXC'],
                ['bybit_commission', '0.1%', 'Комиссия Bybit'],
                ['bingx_commission', '0.1%', 'Комиссия BingX'],
                ['coinex_commission', '0.1%', 'Комиссия CoinEx'],
            ]
        );
    }
}
