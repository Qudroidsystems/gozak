<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_status', ['paid', 'unpaid', 'pending', 'failed', 'refunded'])
                ->default('unpaid')
                ->after('status');

            $table->timestamp('paid_at')->nullable()->after('payment_status');
            // $table->string('invoice_number')->nullable()->unique()->after('id');
            
            $table->index('payment_status');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
