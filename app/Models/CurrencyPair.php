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
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
