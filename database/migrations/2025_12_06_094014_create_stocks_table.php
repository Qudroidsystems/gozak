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
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('stock_location_id')->constrained()->onDelete('cascade');
            $table->foreignId('destination_location_id')->nullable()->constrained('stock_locations')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'return', 'damage'])->default('in');
            $table->integer('quantity')->default(0);
            $table->integer('previous_quantity')->default(0);
            $table->integer('new_quantity')->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 4)->nullable();
            $table->string('reference_number')->nullable();
            $table->enum('reference_type', ['purchase', 'sale', 'return', 'adjustment', 'transfer', 'damage', 'other'])->default('other');
            $table->string('adjustment_reason')->nullable();
            $table->text('notes')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
            
            $table->index('type');
            $table->index('reference_type');
            $table->index('reference_number');
            $table->index('transaction_date');
            $table->index('expiry_date');
            $table->index(['product_id', 'stock_location_id']);
            $table->index(['product_variant_id', 'stock_location_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stocks');
    }
};