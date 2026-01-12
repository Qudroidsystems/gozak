<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('sku');
            $table->index('barcode'); // Optional: add index for faster searches
        });
    }

    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropIndex(['barcode']); // Drop the index first
            $table->dropColumn('barcode');
        });
    }
};
