<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ExchangeApiKey extends Model
{
    protected $fillable = [
        'exchange_id',
        'api_key',
        'api_secret',
    ];

    // Скрываем секретные данные из массивов/JSON
    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    // Автоматическое шифрование/дешифрование
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    public function getApiKeyAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setApiSecretAttribute($value)
    {
        $this->attributes['api_secret'] = Crypt::encryptString($value);
    }

    public function getApiSecretAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function exchange(): BelongsTo
    {
        return $this->belongsTo(Exchange::class);
    }
}
