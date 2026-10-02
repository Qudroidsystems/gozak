<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Speeds up the per-product stock totals the app's /api/products endpoint
 * computes (grouped by product and variant).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stocks', 'product_variant_id')) {
            return;
        }
        try {
            Schema::table('stocks', function (Blueprint $table) {
                $table->index(['product_id', 'product_variant_id', 'type'], 'stocks_product_variant_type_idx');
            });
        } catch (\Throwable $e) {
            // index already exists
        }
    }

    public function down(): void
    {
        try {
            Schema::table('stocks', function (Blueprint $table) {
                $table->dropIndex('stocks_product_variant_type_idx');
            });
        } catch (\Throwable $e) {
        }
    }
};
