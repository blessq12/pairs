<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->string('api_base_url', 1000)->change();
            $table->string('spot_api_url', 1000)->change();
            $table->string('futures_api_url', 1000)->change();
            $table->string('kline_api_url', 1000)->change();
        });
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->string('api_base_url')->change();
            $table->string('spot_api_url')->change();
            $table->string('futures_api_url')->change();
            $table->string('kline_api_url')->change();
        });
    }
}; 