<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds four boolean filter flags to the products table:
     *   - is_new        → manually marked as "New"
     *   - is_trending   → manually marked as "Trending"
     *   - is_top_rated  → manually marked as "Top Rated"
     *
     * NOTE: "On Sale" is NOT stored here. It is derived at query-time:
     *       sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Added after `is_featured` for logical grouping
            $table->boolean('is_new')->default(false)->after('is_featured');
            $table->boolean('is_trending')->default(false)->after('is_new');
            $table->boolean('is_top_rated')->default(false)->after('is_trending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_new', 'is_trending', 'is_top_rated']);
        });
    }
};
