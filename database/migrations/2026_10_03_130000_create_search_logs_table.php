<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** What customers search for — powers "Trending searches" in the app. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('search_logs')) {
            return;
        }
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 120);
            $table->string('normalized', 120)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
