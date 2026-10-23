<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets the admin switch tax (VAT) on/off at checkout without losing the saved rate. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('store_settings', 'tax_enabled')) {
                $table->boolean('tax_enabled')->default(true)->after('tax_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (Schema::hasColumn('store_settings', 'tax_enabled')) {
                $table->dropColumn('tax_enabled');
            }
        });
    }
};
