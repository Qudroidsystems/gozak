<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who sets the GozakMart Delivery fee when it runs through GozakMart's team on the logistics marketplace:
 *   marketplace = the team's price list there
 *   gozakmart   = GozakMart's own shipping fee, sent with each order (if the team's account on the marketplace allows it)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('logistics_settings', 'own_price_source')) $table->string('own_price_source', 12)->default('marketplace');
            // What our delivery account on the marketplace says: shop (we may set the fee) | team (always its price list)
            if (!Schema::hasColumn('logistics_settings', 'own_price_mode')) $table->string('own_price_mode', 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('logistics_settings', fn (Blueprint $t) => $t->dropColumn(['own_price_source', 'own_price_mode']));
    }
};
