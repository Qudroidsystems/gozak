<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::create('order_notes', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('order_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        //     $table->text('note');
        //     $table->boolean('is_customer_visible')->default(false);
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('order_notes');
    }
};
