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

            // Telegram Settings
            $table->string('telegram_bot_token')->nullable()->comment('Токен Telegram бота');
            $table->string('telegram_chat_id')->nullable()->comment('ID чата Telegram для уведомлений');
            $table->string('telegram_message_template')->default('Пара {pair}: профит {profit}% на {exchange}')->comment('Шаблон сообщения для Telegram');

            // Data Storage Settings
            $table->unsignedInteger('price_history_days')->default(90)->comment('Период хранения истории цен (дни)');
            $table->boolean('price_cleanup_enabled')->default(true)->comment('Включить автоматическую очистку старых цен');



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
