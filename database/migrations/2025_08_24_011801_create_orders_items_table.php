<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersItemsTable extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('order_id'); // Changed from foreignId to string to match orders.id
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->string('variation_id')->nullable();
            $table->string('image')->nullable();
            $table->string('brand_name')->nullable();
            $table->json('selected_variation')->nullable();
            $table->timestamps();

            // Add foreign key constraint
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
