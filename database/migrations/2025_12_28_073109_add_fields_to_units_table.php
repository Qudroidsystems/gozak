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
        Schema::table('units', function (Blueprint $table) {
            // Rename 'abbreviation' to 'short_name' if it exists
            // if (Schema::hasColumn('units', 'abbreviation')) {
            //     $table->renameColumn('abbreviation', 'short_name');
            // }

            // Add new columns
            //$table->text('description')->nullable()->after('short_name');
            // $table->boolean('is_default')->default(false)->after('description');
            // $table->boolean('is_active')->default(true)->after('is_default');

            // Remove unique constraints if they exist
            // $table->dropUnique(['name']);
            // $table->dropUnique(['short_name']);
        });

        // Schema::table('product_unit', function (Blueprint $table) {
        //     // Change quantity_per_unit to decimal for more precision
        //     $table->decimal('quantity_per_unit', 10, 2)->default(1)->change();

        //     // Add unique constraint to prevent duplicate product-unit combinations
        //     $table->unique(['product_id', 'unit_id']);
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('units', function (Blueprint $table) {
        //     // Restore unique constraints
        //     $table->unique('name');
        //     $table->unique('short_name');

        //     // Remove added columns
        //     $table->dropColumn(['description', 'is_default', 'is_active']);

        //     // Rename back if needed
        //     if (Schema::hasColumn('units', 'short_name')) {
        //         $table->renameColumn('short_name', 'abbreviation');
        //     }
        // });

        // Schema::table('product_unit', function (Blueprint $table) {
        //     $table->dropUnique(['product_id', 'unit_id']);
        //     $table->integer('quantity_per_unit')->default(1)->change();
        // });
    }
};
