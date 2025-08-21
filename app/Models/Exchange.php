<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class Exchange extends Model
{
    protected $fillable = [
        'name',
        'api_base_url',
        'spot_api_url',
        'futures_api_url',
        'kline_api_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Геттеры и сеттеры для шифрования URL
    public function getApiBaseUrlAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setApiBaseUrlAttribute($value)
    {
        $this->attributes['api_base_url'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSpotApiUrlAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setSpotApiUrlAttribute($value)
    {
        $this->attributes['spot_api_url'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getFuturesApiUrlAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setFuturesApiUrlAttribute($value)
    {
        $this->attributes['futures_api_url'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getKlineApiUrlAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setKlineApiUrlAttribute($value)
    {
        $this->attributes['kline_api_url'] = $value ? Crypt::encryptString($value) : null;
    }



    public function apiKeys(): HasMany
    {
        return $this->hasMany(ExchangeApiKey::class);
    }

    public function currencies(): HasMany
    {
        return $this->hasMany(ExchangeCurrency::class);
    }

    public function exchangePairs(): HasMany
    {
        return $this->hasMany(ExchangePair::class);
    }
}
