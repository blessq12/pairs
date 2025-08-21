<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangePair extends Model
{
    use HasFactory;

    protected $fillable = [
        'exchange_id',
        'base_currency',
        'quote_currency',
        'symbol_on_exchange',
        'is_active',
        'min_amount',
        'maker_fee',
        'taker_fee',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_amount' => 'decimal:8',
        'maker_fee' => 'decimal:6',
        'taker_fee' => 'decimal:6',
    ];

    /**
     * Отношение к бирже
     */
    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    /**
     * Получить символ пары
     */
    public function getSymbolAttribute(): string
    {
        return strtoupper($this->base_currency . $this->quote_currency);
    }

    /**
     * Получить активные пары для биржи
     */
    public static function getActiveForExchange(int $exchangeId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('exchange_id', $exchangeId)
            ->where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Получить все активные пары
     */
    public static function getAllActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Получить активные пары для арбитража
     */
    public static function getActiveForArbitrage(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Получить пары для арбитража по бирже
     */
    public static function getPairsByExchange(int $exchangeId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('exchange_id', $exchangeId)
            ->where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Получить пары для конкретной валютной пары на всех биржах
     */
    public static function getPairsBySymbol(string $baseCurrency, string $quoteCurrency): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('base_currency', strtoupper($baseCurrency))
            ->where('quote_currency', strtoupper($quoteCurrency))
            ->where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Проверить существует ли пара на бирже
     */
    public static function existsOnExchange(int $exchangeId, string $baseCurrency, string $quoteCurrency): bool
    {
        return static::where('exchange_id', $exchangeId)
            ->where('base_currency', strtoupper($baseCurrency))
            ->where('quote_currency', strtoupper($quoteCurrency))
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Получить пары с достаточным объемом для арбитража
     */
    public static function getPairsWithVolume(float $minVolume = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('is_active', true)
            ->with('exchange');

        if ($minVolume !== null) {
            $query->where('min_amount', '>=', $minVolume);
        }

        return $query->get();
    }

    /**
     * Получить все активные пары для арбитража (основной метод)
     */
    public static function getActivePairsForArbitrage(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->with('exchange')
            ->get();
    }

    /**
     * Получить пары для конкретной биржи
     */
    public static function getPairsForExchange(int $exchangeId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('exchange_id', $exchangeId)
            ->where('is_active', true)
            ->with('exchange')
            ->get();
    }
}
