<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurrencyPair extends Model
{
    protected $fillable = [
        'symbol',
        'base_currency',
        'quote_currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->symbol = strtoupper($model->base_currency . $model->quote_currency);
        });
    }

    public function setBaseCurrencyAttribute($value)
    {
        $this->attributes['base_currency'] = strtoupper($value);
    }

    public function setQuoteCurrencyAttribute($value)
    {
        $this->attributes['quote_currency'] = strtoupper($value);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
