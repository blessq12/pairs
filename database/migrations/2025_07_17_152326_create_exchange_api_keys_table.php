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
            $table->string('api_key')->nullable(); // API ключ
            $table->text('api_secret')->nullable(); // API секрет
            $table->text('additional_params')->nullable(); // Дополнительные параметры в JSON
            $table->boolean('is_active')->default(true); // Активен ли ключ
            $table->text('description')->nullable(); // Описание для чего используется
            $table->timestamps();

            // Уникальный ключ для биржи
            $table->unique(['exchange_id', 'api_key']);
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
