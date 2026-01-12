<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('description');
            $table->boolean('is_active')->default(true)->after('is_default');

            // Optional: Ensure only one unit can be default
            // $table->unique('is_default'); // Uncomment if you want strict one default
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_default']);
        });
    }
};
