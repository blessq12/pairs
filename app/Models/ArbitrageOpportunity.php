<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ArbitrageOpportunity extends Model
{
    protected $fillable = [
        'buy_exchange_id',
        'sell_exchange_id',
        'base_currency',
        'quote_currency',
        'buy_price',
        'sell_price',
        'profit_percent',
        'profit_usd',
        'volume_24h_buy',
        'volume_24h_sell',
        'min_volume_usd',
        'buy_commission',
        'sell_commission',
        'total_commission',
        'net_profit_percent',
        'is_active',
        'detected_at',
        'alerted_at',
        'expired_at',
    ];

    protected $casts = [
        'buy_price' => 'decimal:8',
        'sell_price' => 'decimal:8',
        'profit_percent' => 'decimal:4',
        'profit_usd' => 'decimal:2',
        'volume_24h_buy' => 'decimal:2',
        'volume_24h_sell' => 'decimal:2',
        'min_volume_usd' => 'decimal:2',
        'buy_commission' => 'decimal:4',
        'sell_commission' => 'decimal:4',
        'total_commission' => 'decimal:4',
        'net_profit_percent' => 'decimal:4',
        'is_active' => 'boolean',
        'detected_at' => 'datetime',
        'alerted_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    /**
     * Получить только активные возможности
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Получить возможности с профитом выше порога
     */
    public function scopeProfitable(Builder $query, float $minProfit = null): Builder
    {
        $minProfit = $minProfit ?? Setting::get('min_profit_percent', 2.0);
        return $query->where('net_profit_percent', '>=', $minProfit);
    }

    /**
     * Получить возможности с достаточным объёмом
     */
    public function scopeWithVolume(Builder $query, float $minVolume = null): Builder
    {
        $minVolume = $minVolume ?? Setting::get('min_volume_usd', 100.0);
        return $query->where(function ($q) use ($minVolume) {
            $q->where('volume_24h_buy', '>=', $minVolume)
                ->orWhere('volume_24h_sell', '>=', $minVolume);
        });
    }

    /**
     * Получить возможности готовые для алерта (не было алерта или прошло время cooldown)
     */
    public function scopeReadyForAlert(Builder $query): Builder
    {
        $cooldownMinutes = Setting::get('alert_cooldown_minutes', 10);
        return $query->where(function ($q) use ($cooldownMinutes) {
            $q->whereNull('alerted_at')
                ->orWhere('alerted_at', '<=', now()->subMinutes($cooldownMinutes));
        });
    }

    /**
     * Получить возможности за последние N часов
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('detected_at', '>=', now()->subHours($hours));
    }

    /**
     * Отметить как отправленную в алерт
     */
    public function markAsAlerted(): void
    {
        $this->update(['alerted_at' => now()]);
    }

    /**
     * Деактивировать возможность
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Установить время истечения
     */
    public function setExpiration(int $minutes = 30): void
    {
        $this->update(['expired_at' => now()->addMinutes($minutes)]);
    }

    // Relationships
    public function buyExchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class, 'buy_exchange_id');
    }

    public function sellExchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class, 'sell_exchange_id');
    }

    /**
     * Получить символ пары
     */
    public function getSymbolAttribute(): string
    {
        return strtoupper($this->base_currency . $this->quote_currency);
    }
}
