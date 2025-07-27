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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            
            // API Settings
            $table->unsignedInteger('poll_interval')->default(60)->comment('Интервал опроса API бирж (секунды)');
            $table->unsignedInteger('api_timeout')->default(10)->comment('Таймаут запросов к API бирж (секунды)');
            $table->unsignedInteger('retry_attempts')->default(3)->comment('Количество попыток повтора при сбое API');
            
            // Notification Settings
            $table->decimal('profit_threshold', 5, 2)->default(1.0)->comment('Минимальная разница ask/bid для уведомления (%)');
            $table->boolean('notification_enabled')->default(true)->comment('Включить уведомления в Telegram');
            
            // Telegram Settings
            $table->string('telegram_bot_token')->nullable()->comment('Токен Telegram бота');
            $table->string('telegram_chat_id')->nullable()->comment('ID чата Telegram для уведомлений');
            $table->string('telegram_message_template')->default('Пара {pair}: профит {profit}% на {exchange}')->comment('Шаблон сообщения для Telegram');
            
            // Data Storage Settings
            $table->unsignedInteger('price_history_days')->default(90)->comment('Период хранения истории цен (дни)');
            $table->boolean('price_cleanup_enabled')->default(true)->comment('Включить автоматическую очистку старых цен');
            
            // Dashboard Settings
            $table->unsignedInteger('dashboard_refresh_interval')->default(5)->comment('Интервал обновления дашборда (секунды)');
            $table->unsignedInteger('top_pairs_limit')->default(10)->comment('Количество пар в виджете TopPairs');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
