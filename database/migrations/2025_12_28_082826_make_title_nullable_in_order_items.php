<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('order_items', function (Blueprint $table) {
        $table->string('title')->nullable()->change(); // if you want to keep the column
        // OR completely drop it if you don't need historical title:
        // $table->dropColumn('title');

        // Also add missing columns if they don't exist yet
        if (!Schema::hasColumn('order_items', 'unit_id')) {
            $table->foreignId('unit_id')->nullable()->constrained('units');
        }
        if (!Schema::hasColumn('order_items', 'sale_price')) {
            $table->decimal('sale_price', 10, 2)->nullable();
        }
        // Add any other fields like unit_conversion_factor if needed
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            //
        });
    }
};
