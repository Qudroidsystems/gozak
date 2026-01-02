<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('order_id'); // ← MUST be string to match orders.id
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Admin who processed refund
            $table->decimal('amount', 10, 2);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'processed', 'rejected'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Index for performance
            $table->index('order_id');

            // Manual foreign key constraint (string → string)
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
