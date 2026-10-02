<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GozakMart sells in Nigerian Naira: switch the stored store / invoice currency
 * from the original "$ / USD" defaults to "₦ / NGN".
 * Only rows still on the old defaults are touched, so a deliberate choice is kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_settings') && Schema::hasColumn('store_settings', 'currency_symbol')) {
            DB::table('store_settings')
                ->where(fn ($q) => $q->where('currency_symbol', '$')->orWhereNull('currency_symbol'))
                ->update(['currency_symbol' => '₦', 'currency_code' => 'NGN']);

            DB::statement("ALTER TABLE store_settings ALTER currency_symbol SET DEFAULT '₦'");
            DB::statement("ALTER TABLE store_settings ALTER currency_code SET DEFAULT 'NGN'");
        }

        if (Schema::hasTable('invoice_settings')) {
            DB::table('invoice_settings')
                ->where(fn ($q) => $q->where('currency_symbol', '$')->orWhereNull('currency_symbol'))
                ->update(['currency_symbol' => '₦', 'currency' => 'NGN']);

            DB::statement("ALTER TABLE invoice_settings ALTER currency_symbol SET DEFAULT '₦'");
            DB::statement("ALTER TABLE invoice_settings ALTER currency SET DEFAULT 'NGN'");
        }

        cache()->forget('store_settings');
    }

    public function down(): void
    {
        // Intentionally left as-is: switching a live store back to dollars should be a conscious edit.
    }
};
