<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checkout rates the server uses to price orders (and the app shows):
 * tax %, flat shipping fee and the free-shipping threshold. Defaults match
 * what the app had hard-coded (5%, ₦500, free from ₦10,000).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('store_settings', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(5.00);
            }
            if (!Schema::hasColumn('store_settings', 'shipping_fee')) {
                $table->decimal('shipping_fee', 12, 2)->default(500.00);
            }
            if (!Schema::hasColumn('store_settings', 'free_shipping_threshold')) {
                $table->decimal('free_shipping_threshold', 12, 2)->default(10000.00);
            }
        });
        cache()->forget('store_settings');
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            foreach (['tax_rate', 'shipping_fee', 'free_shipping_threshold'] as $c) {
                if (Schema::hasColumn('store_settings', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
        cache()->forget('store_settings');
    }
};
