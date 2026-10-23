<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scan & Shop: customers scan items on the shelf in a GozakMart store, pay in
 * the app and leave; the store packs the items and delivers them.
 *
 *  stock_locations.scan_shop_enabled  this location is a store customers can Scan & Shop in
 *  orders.channel                     app (normal online order) | scan_shop
 *
 * The store itself is saved in the existing orders.stock_location_id, so
 * packing, dispatch and stock all come from that store.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_locations', 'scan_shop_enabled')) {
                $table->boolean('scan_shop_enabled')->default(false)->after('is_active');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'channel')) {
                $table->string('channel', 20)->default('app')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'channel')) {
                $table->dropIndex(['channel']);
                $table->dropColumn('channel');
            }
        });
        Schema::table('stock_locations', function (Blueprint $table) {
            if (Schema::hasColumn('stock_locations', 'scan_shop_enabled')) {
                $table->dropColumn('scan_shop_enabled');
            }
        });
    }
};
