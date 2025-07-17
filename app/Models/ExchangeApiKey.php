<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeApiKey extends Model
{
    protected $fillable = [
        'exchange_id',
        'api_key',
        'api_secret',
        'additional_params',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'additional_params' => 'json',
    ];

    // Скрываем секретные данные из массивов/JSON
    protected $hidden = [
        'api_key',
        'api_secret',
        'additional_params',
    ];

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }
}
