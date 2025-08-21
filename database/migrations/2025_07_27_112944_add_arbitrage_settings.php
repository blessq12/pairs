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
        Schema::table('settings', function (Blueprint $table) {
            // Arbitrage Settings
            $table->decimal('min_profit_percent', 5, 2)->default(2.0)->comment('Минимальный профит для уведомления (%)');
            $table->decimal('min_volume_usd', 10, 2)->default(100.0)->comment('Минимальный объём торгов в USD');
            $table->unsignedInteger('alert_cooldown_minutes')->default(10)->comment('Задержка между алертами по одной паре (минуты)');
            $table->unsignedInteger('poll_interval_minutes')->default(5)->comment('Интервал опроса бирж (минуты)');

            // Exchange Commission Settings
            $table->decimal('mexc_commission', 5, 4)->default(0.001)->comment('Комиссия MEXC (0.1%)');
            $table->decimal('bybit_commission', 5, 4)->default(0.001)->comment('Комиссия Bybit (0.1%)');
            $table->decimal('bingx_commission', 5, 4)->default(0.001)->comment('Комиссия BingX (0.1%)');
            $table->decimal('coinex_commission', 5, 4)->default(0.001)->comment('Комиссия CoinEx (0.1%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'min_profit_percent',
                'min_volume_usd',
                'alert_cooldown_minutes',
                'poll_interval_minutes',
                'mexc_commission',
                'bybit_commission',
                'bingx_commission',
                'coinex_commission',
            ]);
        });
    }
};
