<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->string('spot_api_url')->nullable()->after('api_base_url');
            $table->string('futures_api_url')->nullable()->after('spot_api_url');
            $table->string('kline_api_url')->nullable()->after('futures_api_url');
        });
    }

    public function down(): void
    {
        Schema::table('exchanges', function (Blueprint $table) {
            $table->dropColumn(['spot_api_url', 'futures_api_url', 'kline_api_url']);
        });
    }
};
