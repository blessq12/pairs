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
        Schema::create('exchange_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained()->onDelete('cascade');
            $table->text('api_key'); // Зашифрованный API ключ
            $table->text('api_secret'); // Зашифрованный API секрет
            $table->timestamps();

            // Индекс для быстрого поиска по бирже
            $table->index('exchange_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_api_keys');
    }
};
