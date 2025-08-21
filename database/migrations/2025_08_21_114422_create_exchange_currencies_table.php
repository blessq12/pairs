<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_currencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained()->onDelete('cascade');
            $table->string('currency_symbol', 10); // BTC, USDT, ETH
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Уникальный индекс для пары биржа+валюта
            $table->unique(['exchange_id', 'currency_symbol']);

            // Индексы для быстрого поиска
            $table->index(['exchange_id', 'is_active']);
            $table->index('currency_symbol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_currencies');
    }
};
