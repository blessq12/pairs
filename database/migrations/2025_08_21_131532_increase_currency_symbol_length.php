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
        Schema::table('exchange_currencies', function (Blueprint $table) {
            $table->string('currency_symbol', 50)->change(); // Увеличиваем с 10 до 50
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_currencies', function (Blueprint $table) {
            $table->string('currency_symbol', 10)->change(); // Возвращаем обратно
        });
    }
};
