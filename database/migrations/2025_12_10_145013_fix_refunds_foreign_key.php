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
       // create_order_notes_table.php
        Schema::create('order_notes', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');                    // ← string
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('note');
            $table->boolean('is_customer_visible')->default(false);
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            //
        });
    }
};
