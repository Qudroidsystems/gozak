<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('stock_location_id')->constrained('stock_locations')->onDelete('cascade');
            $table->foreignId('destination_location_id')->nullable()->constrained('stock_locations')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // For product variations
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'return', 'damage'])->default('in');
            $table->integer('quantity')->default(0);
            $table->integer('previous_quantity')->nullable();
            $table->integer('new_quantity')->nullable();
            
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            
            $table->string('reference_number')->nullable()->unique();
            $table->enum('reference_type', ['purchase', 'sale', 'adjustment', 'transfer', 'return', 'damage'])->nullable();
            
            $table->string('adjustment_reason')->nullable();
            $table->text('notes')->nullable();
            
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            
            $table->dateTime('transaction_date')->useCurrent();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['product_id', 'stock_location_id']);
            $table->index('type');
            $table->index('reference_number');
            $table->index('transaction_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks');
    }
};