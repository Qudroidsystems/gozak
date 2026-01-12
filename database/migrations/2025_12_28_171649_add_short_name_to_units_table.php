<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::table('units', function (Blueprint $table) {
        $table->string('short_name')->nullable()->after('name');  // Adjust nullable/default as needed
        // Optional: Add index if you'll search by short_name
        // $table->unique('short_name');
    });
}

public function down(): void
{
    Schema::table('units', function (Blueprint $table) {
        $table->dropColumn('short_name');
    });
}
};
