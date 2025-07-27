<?php

namespace App\Console\Commands;

use App\Enums\KlineInterval;
use App\Models\Setting;
use Illuminate\Console\Command;

class InitParserSettings extends Command
{
    protected $signature = 'pairs:init-parser-settings';
    protected $description = 'Инициализация настроек для парсеров бирж';

    public function handle(): void
    {
        $settings = Setting::firstOrCreate();

        $defaultSettings = [
            'parser_timeout' => 10,
            'parser_connect_timeout' => 5,
            'parser_retry_attempts' => 3,
            'parser_retry_delay' => 1000,
            'parser_kline_limit' => 100,
            'parser_default_interval' => KlineInterval::ONE_MINUTE,
        ];

        foreach ($defaultSettings as $key => $value) {
            if (!isset($settings->$key)) {
                $settings->$key = $value;
            }
        }

        $settings->save();
        Setting::flushCache();

        $this->info('✅ Настройки парсеров успешно инициализированы');
    }
}
