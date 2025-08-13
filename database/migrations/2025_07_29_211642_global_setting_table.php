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
        Schema::create('globalSettings', function (Blueprint $table) {
            $table->id();
            $table->double('tax_rate')->default(0.0);
            $table->double('shipping_cost')->default(0.0);
            $table->double('free_shipping_threshold')->nullable();
            $table->string('app_name')->default('');
            $table->string('app_logo')->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('globalSettings');
    }
};
