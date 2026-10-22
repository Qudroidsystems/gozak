<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns `product_images` into a product media table (photos + short videos).
 *
 *  media_type       image | video
 *  thumb_path       small WebP copy for thumbnails/lists (images)
 *  poster_path      still frame shown before a video plays (videos)
 *  duration_seconds video length
 *  attribute_name / attribute_value
 *                   ties a photo to an option, e.g. Colour = Red, so picking
 *                   "Red" in the app jumps the gallery to the red photos
 *  alt_text         accessibility label
 *
 * Existing rows become media_type = image, so nothing changes for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            if (!Schema::hasColumn('product_images', 'media_type')) {
                $table->string('media_type', 10)->default('image')->after('image_path');
            }
            if (!Schema::hasColumn('product_images', 'thumb_path')) {
                $table->string('thumb_path')->nullable()->after('media_type');
            }
            if (!Schema::hasColumn('product_images', 'poster_path')) {
                $table->string('poster_path')->nullable()->after('thumb_path');
            }
            if (!Schema::hasColumn('product_images', 'duration_seconds')) {
                $table->unsignedSmallInteger('duration_seconds')->nullable()->after('poster_path');
            }
            if (!Schema::hasColumn('product_images', 'attribute_name')) {
                $table->string('attribute_name')->nullable()->after('duration_seconds');
            }
            if (!Schema::hasColumn('product_images', 'attribute_value')) {
                $table->string('attribute_value')->nullable()->after('attribute_name');
            }
            if (!Schema::hasColumn('product_images', 'alt_text')) {
                $table->string('alt_text')->nullable()->after('attribute_value');
            }
            // Defensive: these were in the original create migration, but
            // older databases have been seen without them.
            if (!Schema::hasColumn('product_images', 'is_primary')) {
                $table->boolean('is_primary')->default(false);
            }
            if (!Schema::hasColumn('product_images', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index(['product_id', 'sort_order'], 'product_images_product_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_product_sort_idx');
            foreach (['media_type', 'thumb_path', 'poster_path', 'duration_seconds', 'attribute_name', 'attribute_value', 'alt_text'] as $col) {
                if (Schema::hasColumn('product_images', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
