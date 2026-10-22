<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jumia-style "Specifications" list (Material: Cotton, Warranty: 1 year …)
 * and an optional size guide per product.
 *
 * size_guide JSON shape:
 *   {
 *     "unit": "cm",
 *     "columns": ["Size", "Chest", "Waist", "Length"],
 *     "rows": [["S", "86-91", "71-76", "68"], ["M", "96-101", "81-86", "70"]],
 *     "note": "Measure around the fullest part of the chest."
 *   }
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_specifications')) {
            Schema::create('product_specifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('label');                 // "Material"
                $table->string('value', 1000);           // "100% Cotton"
                $table->string('group')->nullable();     // optional heading, e.g. "Dimensions"
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['product_id', 'sort_order']);
            });
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'size_guide')) {
                $table->json('size_guide')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specifications');

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'size_guide')) {
                $table->dropColumn('size_guide');
            }
        });
    }
};
