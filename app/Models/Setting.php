<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'poll_interval' => 'integer',
        'api_timeout' => 'integer',
        'retry_attempts' => 'integer',
        'profit_threshold' => 'float',
        'notification_enabled' => 'boolean',
        'price_history_days' => 'integer',
        'price_cleanup_enabled' => 'boolean',
        'dashboard_refresh_interval' => 'integer',
        'top_pairs_limit' => 'integer',
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
        return Cache::rememberForever('settings', function () {
            return self::firstOrCreate()->toArray();
        });
    }

    /**
     * Очистить кэш настроек
     */
    public static function flushCache(): void
    {
        Cache::forget('settings');
    }
}
