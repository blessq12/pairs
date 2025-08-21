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
        Schema::table('exchange_pairs', function (Blueprint $table) {
            // Сначала удаляем внешний ключ
            $table->dropForeign(['currency_pair_id']);

            // Удаляем старые индексы
            $table->dropUnique(['exchange_id', 'currency_pair_id']);
            $table->dropIndex(['currency_pair_id', 'is_active']);

            // Теперь удаляем колонку
            $table->dropColumn('currency_pair_id');

            // Добавляем новые индексы
            $table->unique(['exchange_id', 'base_currency', 'quote_currency']);
            $table->index(['base_currency', 'quote_currency', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_pairs', function (Blueprint $table) {
            // Возвращаем старую структуру
            $table->dropUnique(['exchange_id', 'base_currency', 'quote_currency']);
            $table->dropIndex(['base_currency', 'quote_currency', 'is_active']);

            $table->unsignedBigInteger('currency_pair_id')->after('exchange_id');

            $table->unique(['exchange_id', 'currency_pair_id']);
            $table->index(['currency_pair_id', 'is_active']);
        });
    }
};
