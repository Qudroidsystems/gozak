<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dispatch, part 2:
 *  - delivery codes (OTP) + photo proof + rider location per stop
 *  - cash collected per stop and cash handed in per run
 *  - dispatch_events: every assign / dispatch / call / delivery / failure (feeds performance)
 *  - dispatch_settings: OTP rules, capacities and SLA thresholds
 *  - "Deliver orders" permission + "Rider" role for the rider app
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $add = fn (string $c) => !Schema::hasColumn('orders', $c);
            if ($add('delivery_code'))          $table->string('delivery_code', 8)->nullable();
            if ($add('delivery_code_attempts')) $table->unsignedTinyInteger('delivery_code_attempts')->default(0);
            if ($add('delivery_method'))        $table->string('delivery_method', 16)->nullable(); // code | photo | admin | none
            if ($add('delivery_proof'))         $table->string('delivery_proof')->nullable();      // storage path of photo
            if ($add('delivery_receiver'))      $table->string('delivery_receiver', 120)->nullable();
            if ($add('delivered_lat'))          $table->decimal('delivered_lat', 10, 7)->nullable();
            if ($add('delivered_lng'))          $table->decimal('delivered_lng', 10, 7)->nullable();
            if ($add('delivery_attempts'))      $table->unsignedTinyInteger('delivery_attempts')->default(0);
            if ($add('out_for_delivery_at'))    $table->timestamp('out_for_delivery_at')->nullable();
            if ($add('cash_collected'))         $table->decimal('cash_collected', 12, 2)->nullable();
            if ($add('cash_method'))            $table->string('cash_method', 16)->nullable(); // cash | transfer | pos
        });

        Schema::table('dispatch_batches', function (Blueprint $table) {
            $add = fn (string $c) => !Schema::hasColumn('dispatch_batches', $c);
            if ($add('cash_expected'))      $table->decimal('cash_expected', 12, 2)->default(0);
            if ($add('cash_received'))      $table->decimal('cash_received', 12, 2)->nullable(); // handed in at the store
            if ($add('cash_received_by'))   $table->unsignedBigInteger('cash_received_by')->nullable();
            if ($add('cash_reconciled_at')) $table->timestamp('cash_reconciled_at')->nullable();
            if ($add('cash_note'))          $table->string('cash_note', 500)->nullable();
            if ($add('suggested'))          $table->boolean('suggested')->default(false); // created from an auto-suggestion
        });

        if (!Schema::hasTable('dispatch_events')) {
            Schema::create('dispatch_events', function (Blueprint $table) {
                $table->id();
                $table->string('order_id', 191)->nullable()->index();
                $table->unsignedBigInteger('dispatch_batch_id')->nullable()->index();
                $table->unsignedBigInteger('rider_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable();
                // assigned | dispatched | delivered | failed | code_failed | called | returned | cash_in
                $table->string('event', 24)->index();
                $table->string('note', 500)->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (!Schema::hasTable('dispatch_settings')) {
            Schema::create('dispatch_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('require_delivery_code')->default(true);
                $table->boolean('allow_photo_fallback')->default(true);
                $table->unsignedTinyInteger('max_code_attempts')->default(5);
                $table->unsignedSmallInteger('default_capacity')->default(12);      // parcels, when a rider has none set
                $table->unsignedSmallInteger('max_stops_per_run')->default(12);
                $table->unsignedTinyInteger('default_promise_hour')->default(18);   // promised "by 6pm" when no time is set
                $table->unsignedSmallInteger('pack_within_minutes')->default(120);  // alert: order not packed
                $table->unsignedSmallInteger('at_risk_minutes')->default(120);      // alert: promise is this close
                $table->unsignedSmallInteger('loading_stuck_minutes')->default(60); // alert: batch not dispatched
                $table->unsignedSmallInteger('run_overdue_minutes')->default(240);  // alert: run still out
                $table->timestamps();
            });
            DB::table('dispatch_settings')->insert(['created_at' => now(), 'updated_at' => now()]);
        }

        if (class_exists(\Spatie\Permission\Models\Permission::class) && Schema::hasTable('permissions')) {
            $hasMeta = Schema::hasColumn('permissions', 'title');
            $deliver = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'Deliver orders', 'guard_name' => 'web'],
                $hasMeta ? ['title' => 'Deliver orders (rider app)', 'description' => 'Use the rider app: see own runs, call customers, confirm deliveries.'] : []
            );
            $settings = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'Manage dispatch settings', 'guard_name' => 'web'],
                $hasMeta ? ['title' => 'Manage dispatch settings', 'description' => 'Delivery codes, rider capacity and late-order alert thresholds.'] : []
            );
            $rider = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Rider', 'guard_name' => 'web']);
            if (!$rider->hasPermissionTo($deliver)) {
                $rider->givePermissionTo($deliver);
            }
            $admin = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
            if ($admin) {
                foreach ([$deliver, $settings] as $p) {
                    if (!$admin->hasPermissionTo($p)) {
                        $admin->givePermissionTo($p);
                    }
                }
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_settings');
        Schema::dropIfExists('dispatch_events');
        Schema::table('dispatch_batches', function (Blueprint $table) {
            foreach (['cash_expected', 'cash_received', 'cash_received_by', 'cash_reconciled_at', 'cash_note', 'suggested'] as $c) {
                if (Schema::hasColumn('dispatch_batches', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('orders', function (Blueprint $table) {
            foreach (['delivery_code', 'delivery_code_attempts', 'delivery_method', 'delivery_proof', 'delivery_receiver', 'delivered_lat',
                      'delivered_lng', 'delivery_attempts', 'out_for_delivery_at', 'cash_collected', 'cash_method'] as $c) {
                if (Schema::hasColumn('orders', $c)) $table->dropColumn($c);
            }
        });
    }
};
