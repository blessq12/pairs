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
        'currency_pair_id',
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
     * Отношение к валютной паре
     */
    public function currencyPair(): BelongsTo
    {
        return $this->belongsTo(CurrencyPair::class);
    }

    /**
     * Получить активные пары для биржи
     */
    public static function getActiveForExchange(int $exchangeId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('exchange_id', $exchangeId)
            ->where('is_active', true)
            ->with(['currencyPair', 'exchange'])
            ->get();
    }

    /**
     * Получить активные пары для валютной пары
     */
    public static function getActiveForPair(int $pairId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('currency_pair_id', $pairId)
            ->where('is_active', true)
            ->with(['currencyPair', 'exchange'])
            ->get();
    }

    /**
     * Получить все активные пары
     */
    public static function getAllActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->with(['currencyPair', 'exchange'])
            ->get();
    }

    /**
     * Получить активные пары для арбитража (сгруппированные по валютной паре)
     */
    public static function getActiveForArbitrage(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->with(['currencyPair', 'exchange'])
            ->get();
    }

    /**
     * Получить пары для арбитража по конкретной валютной паре
     */
    public static function getPairsForArbitrage(int $currencyPairId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('currency_pair_id', $currencyPairId)
            ->where('is_active', true)
            ->with(['currencyPair', 'exchange'])
            ->get();
    }

    /**
     * Получить пары для арбитража по бирже
     */
    public static function getPairsByExchange(int $exchangeId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('exchange_id', $exchangeId)
            ->where('is_active', true)
            ->with(['currencyPair', 'exchange'])
            ->get();
    }

    /**
     * Получить пары для арбитража по валютной паре
     */
    public static function getPairsByCurrencyPair(int $pairId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('currency_pair_id', $pairId)
            ->where('is_active', true)
            ->with(['currencyPair', 'exchange'])
            ->get();
    }

    /**
     * Проверить существует ли пара на бирже
     */
    public static function existsOnExchange(int $exchangeId, int $pairId): bool
    {
        return static::where('exchange_id', $exchangeId)
            ->where('currency_pair_id', $pairId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Получить пары с достаточным объемом для арбитража
     */
    public static function getPairsWithVolume(float $minVolume = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('is_active', true)
            ->with(['currencyPair', 'exchange']);

        if ($minVolume !== null) {
            $query->where('min_amount', '>=', $minVolume);
        }

        return $query->get();
    }
}
