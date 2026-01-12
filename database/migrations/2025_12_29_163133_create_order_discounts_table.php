<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Temporarily disable foreign key checks to bypass strict validation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();

            $table->string('order_id', 255); // Match orders.id length
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->foreign('order_item_id')
                  ->references('id')
                  ->on('order_items')
                  ->onDelete('cascade');

            $table->enum('type', ['order', 'item']);
            $table->enum('discount_type', ['percent', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('amount', 15, 2);

            $table->foreignId('applied_by')->constrained('users');

            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();

            $table->index('order_id');
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        Schema::dropIfExists('order_discounts');
    }
};
