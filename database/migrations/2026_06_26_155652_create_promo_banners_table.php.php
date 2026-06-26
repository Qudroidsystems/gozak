<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();

            // Content
            $table->string('badge_text', 80);                        // e.g. "⚡ TODAY ONLY"
            $table->string('title', 120);
            $table->string('subtitle', 300);
            $table->string('cta_text', 60)->default('Shop Now');
            $table->string('cta_route')->nullable();                 // named Flutter route or deep-link

            // Visuals
            $table->string('image_url')->nullable();                 // stored path
            $table->string('gradient_start', 9)->default('#FF4E50'); // hex
            $table->string('gradient_end', 9)->default('#F9A720');   // hex
            $table->string('accent_color', 9)->default('#FFD700');   // hex

            // Scheduling & targeting
            $table->string('target_screen')->default('all');         // home | category | offers | all
            $table->boolean('active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);  // display order

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_banners');
    }
};
