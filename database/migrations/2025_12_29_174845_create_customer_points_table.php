<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Customer Points (one row per customer)
        Schema::create('customer_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->decimal('points_value', 15, 2)->default(0.00); // Optional cached value
            $table->timestamps();

            $table->unique('customer_id'); // One row per customer
        });

        // 2. Customer Point Transactions (history)
        Schema::create('customer_point_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                  ->constrained('customers')
                  ->onDelete('cascade');

            // String foreign key to match orders.id (string primary key)
            $table->string('order_id', 255)->nullable();
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('set null');

            $table->integer('points_earned')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->decimal('amount_spent', 15, 2)->default(0.00);
            $table->decimal('discount_applied', 15, 2)->default(0.00);
            $table->string('description');

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamps();

            // Indexes for performance
            $table->index('customer_id');
            $table->index('order_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_point_transactions');
        Schema::dropIfExists('customer_points');
    }
};
