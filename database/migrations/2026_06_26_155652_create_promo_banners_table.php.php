<?php
// database/migrations/2024_01_01_000000_create_promo_banners_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();

            // Content fields
            $table->string('badge_text', 80)->default('⚡ SPECIAL');
            $table->string('title', 120);
            $table->string('subtitle', 300);
            $table->string('cta_text', 60);
            $table->string('cta_route', 120)->nullable();

            // Visual
            $table->string('image_url')->nullable();
            $table->string('lottie_asset')->nullable(); // optional animation
            $table->string('gradient_start', 7)->default('#FF4E50');
            $table->string('gradient_end', 7)->default('#F9A720');
            $table->string('accent_color', 7)->default('#FFD700');

            // Targeting
            $table->enum('target_screen', ['home', 'category', 'product', 'offers', 'all'])->default('all');

            // Scheduling
            $table->boolean('active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('sort_order')->default(0);

            // For showing once per day
            $table->boolean('show_once_daily')->default(true);

            $table->timestamps();

            // Indexes for performance
            $table->index(['active', 'starts_at', 'ends_at']);
            $table->index('target_screen');
            $table->index('sort_order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('promo_banners');
    }
};
