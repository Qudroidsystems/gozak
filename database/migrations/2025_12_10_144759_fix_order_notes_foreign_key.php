<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the broken table if it exists
        Schema::dropIfExists('order_notes');

        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->string('order_id'); // ← Must be string to match orders.id
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('note');
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamps();

            // Now this will work!
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notes');
    }
};

 
