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
        Schema::table('arbitrage_opportunities', function (Blueprint $table) {
            // Добавляем новый уникальный индекс с коротким именем
            $table->unique(['buy_exchange_id', 'sell_exchange_id', 'base_currency', 'quote_currency'], 'arbitrage_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arbitrage_opportunities', function (Blueprint $table) {
            // Удаляем индекс
            $table->dropUnique('arbitrage_unique');
        });
    }
};
