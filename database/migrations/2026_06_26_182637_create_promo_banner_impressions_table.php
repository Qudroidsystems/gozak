<?php
// database/migrations/2024_01_01_000001_create_promo_banner_impressions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('promo_banner_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained('promo_banners')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('shown_at');
            $table->string('screen')->nullable();
            $table->timestamps();

            $table->index(['banner_id', 'shown_at']);
            $table->index(['user_id', 'shown_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('promo_banner_impressions');
    }
};
