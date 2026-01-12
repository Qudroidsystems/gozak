<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('currency_symbol', 10)->default('$')->after('store_name');
            $table->string('currency_code', 3)->default('USD')->after('currency_symbol');
            $table->string('website')->nullable()->after('email');
        });

        // Optional: Update default row with new fields
        \DB::table('store_settings')->update([
            'currency_symbol' => '$',
            'currency_code'   => 'USD',
        ]);
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['currency_symbol', 'currency_code', 'website']);
        });
    }
};
