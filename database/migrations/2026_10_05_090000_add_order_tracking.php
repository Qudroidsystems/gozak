<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Order tracking: status timeline, delivery timestamps, customer
 * "I've received it" confirmation and auto-confirm settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'received_confirmed_at')) {
                $table->timestamp('received_confirmed_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'delivery_confirmed_by')) {
                // customer | admin | auto
                $table->string('delivery_confirmed_by', 16)->nullable();
            }
        });

        if (!Schema::hasTable('order_status_histories')) {
            Schema::create('order_status_histories', function (Blueprint $table) {
                $table->id();
                $table->string('order_id', 191)->index();
                $table->string('from_status', 32)->nullable();
                $table->string('status', 32);
                $table->string('actor_type', 16)->default('system'); // admin | customer | system
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('note', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_settings')) {
            Schema::create('order_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('auto_confirm_enabled')->default(true);
                $table->unsignedSmallInteger('auto_confirm_days')->default(5);
                $table->timestamps();
            });
            DB::table('order_settings')->insert([
                'auto_confirm_enabled' => true,
                'auto_confirm_days'    => 5,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        // Back-fill timestamps for orders that are already shipped / delivered.
        DB::table('orders')->where('status', 'shipped')->whereNull('shipped_at')->update(['shipped_at' => DB::raw('updated_at')]);
        DB::table('orders')->where('status', 'delivered')->whereNull('delivered_at')->update(['delivered_at' => DB::raw('updated_at')]);
        // Old delivered orders count as confirmed so nobody is asked about them.
        DB::table('orders')->where('status', 'delivered')->whereNull('received_confirmed_at')->update([
            'received_confirmed_at' => DB::raw('updated_at'),
            'delivery_confirmed_by' => 'admin',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_settings');
        Schema::dropIfExists('order_status_histories');
        Schema::table('orders', function (Blueprint $table) {
            foreach (['shipped_at', 'delivered_at', 'received_confirmed_at', 'delivery_confirmed_by'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
