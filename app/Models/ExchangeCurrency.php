<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeCurrency extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_id',
        'currency_symbol',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Отношение к бирже
     */
    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    /**
     * Получить активные валюты для биржи
     */
    public static function getActiveForExchange(int $exchangeId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('exchange_id', $exchangeId)
            ->where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Получить все активные валюты
     */
    public static function getAllActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Проверить существует ли валюта на бирже
     */
    public static function existsOnExchange(int $exchangeId, string $currencySymbol): bool
    {
        return static::where('exchange_id', $exchangeId)
            ->where('currency_symbol', strtoupper($currencySymbol))
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Получить все валюты по символу
     */
    public static function getBySymbol(string $currencySymbol): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('currency_symbol', strtoupper($currencySymbol))
            ->where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Получить все уникальные валюты для select
     */
    public static function getAllUniqueCurrencies(): array
    {
        return static::where('is_active', true)
            ->distinct()
            ->pluck('currency_symbol')
            ->sort()
            ->toArray();
    }

    /**
     * Получить валюты для конкретной биржи
     */
    public static function getCurrenciesForExchange(int $exchangeId): array
    {
        return static::where('exchange_id', $exchangeId)
            ->where('is_active', true)
            ->pluck('currency_symbol')
            ->sort()
            ->toArray();
    }
}
