<?php

namespace App\Models;

use App\Enums\KlineInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        // Parser settings
        'parser_timeout' => 'integer',
        'parser_connect_timeout' => 'integer',
        'parser_retry_attempts' => 'integer',
        'parser_retry_delay' => 'integer',
        'parser_kline_limit' => 'integer',
        'parser_default_interval' => KlineInterval::class,

        // Existing settings
        'poll_interval' => 'integer',
        'api_timeout' => 'integer',
        'retry_attempts' => 'integer',
        'profit_threshold' => 'float',
        'notification_enabled' => 'boolean',
        'price_history_days' => 'integer',
        'price_cleanup_enabled' => 'boolean',
        'dashboard_refresh_interval' => 'integer',
        'top_pairs_limit' => 'integer',

        // Arbitrage settings
        'min_profit_percent' => 'float',
        'min_volume_usd' => 'float',
        'alert_cooldown_minutes' => 'integer',
        'poll_interval_minutes' => 'integer',

        // Exchange commission settings
        'mexc_commission' => 'float',
        'bybit_commission' => 'float',
        'bingx_commission' => 'float',
        'coinex_commission' => 'float',
    ];

    /**
     * Получить значение настройки
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever('settings', function () {
            return self::firstOrCreate();
        })->$key ?? $default;
    }

    /**
     * Установить значение настройки
     */
    public static function set(string $key, mixed $value): void
    {
        $settings = self::firstOrCreate();
        $settings->$key = $value;
        $settings->save();

        self::flushCache();
    }

    /**
     * Получить все настройки
     */
    public static function getAll(): array
    {
        $settings = self::firstOrCreate();
        return $settings->getAttributes();
    }

    /**
     * Очистить кэш настроек
     */
    public static function flushCache(): void
    {
        Cache::forget('settings');
    }
}
