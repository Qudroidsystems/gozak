<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "GozakMart Delivery" can run through GozakMart's own delivery team on the logistics marketplace:
 * the order is booked to that team, its staff assign a rider there, and the customer follows the rider live.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('logistics_settings', 'own_via_marketplace')) $table->boolean('own_via_marketplace')->default(false);
            if (!Schema::hasColumn('logistics_settings', 'own_provider_name'))   $table->string('own_provider_name')->nullable();
        });
        Schema::table('order_shipments', function (Blueprint $table) {
            // courier = a marketplace provider the customer chose; own = GozakMart Delivery run by GozakMart's own team there
            if (!Schema::hasColumn('order_shipments', 'mode')) $table->string('mode', 12)->default('courier');
        });
    }

    public function down(): void
    {
        Schema::table('order_shipments', fn (Blueprint $t) => $t->dropColumn('mode'));
        Schema::table('logistics_settings', fn (Blueprint $t) => $t->dropColumn(['own_via_marketplace', 'own_provider_name']));
    }
};
