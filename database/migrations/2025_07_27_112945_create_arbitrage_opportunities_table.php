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
        Schema::create('arbitrage_opportunities', function (Blueprint $table) {
            $table->id();

            // Exchange and Pair Info
            $table->unsignedBigInteger('buy_exchange_id')->comment('Биржа для покупки');
            $table->unsignedBigInteger('sell_exchange_id')->comment('Биржа для продажи');
            $table->unsignedBigInteger('currency_pair_id')->comment('Валютная пара');

            // Price Information
            $table->decimal('buy_price', 16, 8)->comment('Цена покупки');
            $table->decimal('sell_price', 16, 8)->comment('Цена продажи');
            $table->decimal('profit_percent', 8, 4)->comment('Процент профита');
            $table->decimal('profit_usd', 12, 2)->comment('Профит в USD (при объёме 1000$)');

            // Volume Information
            $table->decimal('volume_24h_buy', 16, 2)->nullable()->comment('Объём торгов за 24ч на бирже покупки');
            $table->decimal('volume_24h_sell', 16, 2)->nullable()->comment('Объём торгов за 24ч на бирже продажи');
            $table->decimal('min_volume_usd', 10, 2)->comment('Минимальный объём для торговли');

            // Commission Information
            $table->decimal('buy_commission', 8, 4)->comment('Комиссия биржи покупки');
            $table->decimal('sell_commission', 8, 4)->comment('Комиссия биржи продажи');
            $table->decimal('total_commission', 8, 4)->comment('Общая комиссия');
            $table->decimal('net_profit_percent', 8, 4)->comment('Чистый профит после комиссий');

            // Status and Timing
            $table->boolean('is_active')->default(true)->comment('Активна ли возможность');
            $table->timestamp('detected_at')->useCurrent()->comment('Время обнаружения');
            $table->timestamp('alerted_at')->nullable()->comment('Время последнего алерта');
            $table->timestamp('expired_at')->nullable()->comment('Время истечения возможности');

            // Indexes
            $table->index(['buy_exchange_id', 'sell_exchange_id', 'currency_pair_id'], 'arbitrage_exchanges_pair');
            $table->index('profit_percent');
            $table->index('detected_at');
            $table->index('is_active');

            // Foreign Keys
            $table->foreign('buy_exchange_id')->references('id')->on('exchanges')->onDelete('cascade');
            $table->foreign('sell_exchange_id')->references('id')->on('exchanges')->onDelete('cascade');
            $table->foreign('currency_pair_id')->references('id')->on('currency_pairs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbitrage_opportunities');
    }
};
