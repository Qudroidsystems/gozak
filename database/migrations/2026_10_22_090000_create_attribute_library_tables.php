<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attribute library (Jumia/Temu style).
 *
 * `variants`        — reusable option types: Size, Colour, Shoe Size, Storage …
 * `variant_values`  — their values: S, M, L … / Red (#E53935) …
 *
 * Products still store their chosen attribute names + values on
 * `product_attributes` (unchanged, so old products and the app version
 * already on phones keep working). `product_attributes` just gains a link
 * back to the library entry and a display type, so the app knows to draw
 * colour swatches, size buttons or photo chips.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('variants')) {
            Schema::create('variants', function (Blueprint $table) {
                $table->id();
                $table->string('name');                        // "Size"
                $table->string('slug')->unique();              // "size"
                // How the app draws the choices:
                //   button = text chips (default), size = compact size boxes,
                //   color  = round colour swatches, image = photo chips
                $table->string('display_type', 20)->default('button');
                $table->string('hint')->nullable();            // e.g. "UK sizing"
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('variant_values')) {
            Schema::create('variant_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('variant_id')->constrained('variants')->cascadeOnDelete();
                $table->string('value');                       // "XL" / "Navy Blue"
                $table->string('color_hex', 9)->nullable();    // "#1A237E" for colour swatches
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['variant_id', 'value']);
                $table->index(['variant_id', 'sort_order']);
            });
        }

        Schema::table('product_attributes', function (Blueprint $table) {
            if (!Schema::hasColumn('product_attributes', 'variant_id')) {
                $table->foreignId('variant_id')->nullable()->after('product_id')
                      ->constrained('variants')->nullOnDelete();
            }
            if (!Schema::hasColumn('product_attributes', 'display_type')) {
                $table->string('display_type', 20)->nullable()->after('name');
            }
            if (!Schema::hasColumn('product_attributes', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('values');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_attributes', function (Blueprint $table) {
            if (Schema::hasColumn('product_attributes', 'variant_id')) {
                $table->dropConstrainedForeignId('variant_id');
            }
            foreach (['display_type', 'sort_order'] as $col) {
                if (Schema::hasColumn('product_attributes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('variant_values');
        Schema::dropIfExists('variants');
    }
};
