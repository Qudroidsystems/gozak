<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->nullable()->after('price');
            $table->dropColumn('stock'); // Remove stock column since it's managed by inventory
        });
    }

    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn('cost_price');
            $table->integer('stock')->default(0)->after('sale_price');
        });
    }
};
