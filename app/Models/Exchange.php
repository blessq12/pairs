<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exchange extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ExchangeApiKey::class);
    }
}
