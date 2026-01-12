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
            // Add customer_id column
            $table->foreignId('customer_id')->nullable()->after('id')->constrained()->nullOnDelete();

            // Make user_id nullable since we're using customer_id now
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');

            // Revert user_id to not nullable if needed
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
