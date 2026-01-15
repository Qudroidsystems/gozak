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
        Schema::table('orders', function (Blueprint $table) {
            // Change barcode_data column to LONGTEXT
            $table->longText('barcode_data')->nullable()->change();

            // Also ensure barcode_path column exists
            if (!Schema::hasColumn('orders', 'barcode_path')) {
                $table->string('barcode_path')->nullable()->after('barcode_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Change back to TEXT (not all databases support TEXT to LONGTEXT rollback)
            $table->text('barcode_data')->nullable()->change();

            // Only drop barcode_path if it was created in this migration
            // Don't drop if it already existed
        });
    }
};
