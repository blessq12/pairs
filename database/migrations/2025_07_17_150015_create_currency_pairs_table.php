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
        Schema::create('currency_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique(); // Например, BTCUSDT
            $table->string('base_currency'); // BTC
            $table->string('quote_currency'); // USDT
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_pairs');
    }
};
