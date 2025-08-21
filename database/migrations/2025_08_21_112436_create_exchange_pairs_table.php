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
        Schema::create('exchange_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained()->onDelete('cascade');
            $table->string('base_currency', 10); // Базовая валюта (например, BTC)
            $table->string('quote_currency', 10); // Котируемая валюта (например, USDT)
            $table->string('symbol_on_exchange'); // Как пара называется на бирже (например, BTCUSDT)
            $table->boolean('is_active')->default(true);
            $table->decimal('min_amount', 20, 8)->nullable(); // Минимальный объем для торговли
            $table->decimal('maker_fee', 8, 6)->nullable(); // Комиссия мейкера
            $table->decimal('taker_fee', 8, 6)->nullable(); // Комиссия тейкера
            $table->timestamps();

            // Уникальный индекс для пары биржа+базовая+котируемая
            $table->unique(['exchange_id', 'base_currency', 'quote_currency']);

            // Индексы для быстрого поиска
            $table->index(['exchange_id', 'is_active']);
            $table->index(['base_currency', 'quote_currency', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_pairs');
    }
};
