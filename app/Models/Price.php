<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Price extends Model
{
    protected $fillable = [
        'exchange_id',
        'currency_pair_id',
        'bid',
        'ask',
        'fetched_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
        'bid' => 'decimal:8',
        'ask' => 'decimal:8',
    ];

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }

    public function currencyPair(): BelongsTo
    {
        return $this->belongsTo(CurrencyPair::class);
    }
}
