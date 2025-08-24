<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id')->primary(); // Use string for UUID or custom ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0.00);
            $table->decimal('tax_cost', 10, 2)->default(0.00);
            $table->dateTime('order_date');
            $table->string('payment_method')->default('Cash on Delivery');
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->onDelete('set null');
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses')->onDelete('set null');
            $table->dateTime('delivery_date')->nullable();
            $table->boolean('billing_address_same_as_shipping')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}