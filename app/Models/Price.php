<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Price extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'exchange_id',
        'currency_pair_id',
        'bid_price',
        'ask_price',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'bid_price' => 'decimal:8',
        'ask_price' => 'decimal:8',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($price) {
            // Проверяем существование биржи
            if (!Exchange::where('id', $price->exchange_id)->exists()) {
                throw new \RuntimeException("Exchange with ID {$price->exchange_id} does not exist");
            }

            // Проверяем существование валютной пары
            if (!CurrencyPair::where('id', $price->currency_pair_id)->exists()) {
                throw new \RuntimeException("CurrencyPair with ID {$price->currency_pair_id} does not exist");
            }
        });
    }

    /**
     * Получить только актуальные цены (не старше 3 месяцев)
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subMonths(3));
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    public function currencyPair(): BelongsTo
    {
        return $this->belongsTo(CurrencyPair::class);
    }
}
