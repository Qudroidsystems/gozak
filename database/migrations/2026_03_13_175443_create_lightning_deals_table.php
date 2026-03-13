<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lightning_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->unsignedTinyInteger('discount_percentage');   // 1–99
            $table->unsignedInteger('stock_limit')->nullable();   // null = use product stock
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'ends_at']);
            $table->unique('product_id'); // one active deal per product
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lightning_deals');
    }
};
