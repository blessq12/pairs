<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\KlineInterval;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Таймауты и ретраи
            $table->integer('parser_timeout')->default(10);
            $table->integer('parser_connect_timeout')->default(5);
            $table->integer('parser_retry_attempts')->default(3);
            $table->integer('parser_retry_delay')->default(1000); // в миллисекундах

            // Лимиты данных
            $table->integer('parser_kline_limit')->default(100);

            // Форматы
            $table->enum('parser_default_interval', KlineInterval::values())->default(KlineInterval::ONE_MINUTE->value);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'parser_timeout',
                'parser_connect_timeout',
                'parser_retry_attempts',
                'parser_retry_delay',
                'parser_kline_limit',
                'parser_default_interval',
            ]);
        });
    }
};
