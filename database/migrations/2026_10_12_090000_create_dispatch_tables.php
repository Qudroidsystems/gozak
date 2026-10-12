<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parcel labelling + dispatch.
 *
 *  delivery_zones    – areas a branch delivers to (matched against address LGA / city / street)
 *  delivery_riders   – riders and their vehicles
 *  dispatch_batches  – one delivery run: rider + route code + ordered stops
 *  order_parcels     – every bag / carton of an order, each with its own code
 *
 *  orders get a branch, packing sign-off and their place in a batch;
 *  order_items get picked quantity + substitution notes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_zones')) {
            Schema::create('delivery_zones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('stock_location_id')->nullable()->index(); // branch
                $table->string('code', 12);                 // short route prefix, e.g. IKJ
                $table->string('name');                     // e.g. Ikeja / Allen / Opebi
                $table->text('areas')->nullable();          // JSON list of LGAs, towns, estates, streets
                $table->string('color', 9)->nullable();     // label colour band
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['stock_location_id', 'code']);
            });
        }

        if (!Schema::hasTable('delivery_riders')) {
            Schema::create('delivery_riders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();          // optional staff login
                $table->unsignedBigInteger('stock_location_id')->nullable()->index(); // home branch
                $table->string('name');
                $table->string('phone', 32)->nullable();
                $table->string('vehicle_type', 20)->default('motorbike'); // motorbike | tricycle | car | van | bicycle | on_foot
                $table->string('vehicle_plate', 32)->nullable();
                $table->unsignedSmallInteger('capacity')->nullable();    // max parcels per run
                $table->boolean('is_active')->default(true);
                $table->string('notes', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dispatch_batches')) {
            Schema::create('dispatch_batches', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();       // DSP-261012-003
                $table->string('route_code', 32)->index();  // IKJ-02
                $table->unsignedBigInteger('stock_location_id')->nullable()->index();
                $table->unsignedBigInteger('delivery_zone_id')->nullable()->index();
                $table->unsignedBigInteger('rider_id')->nullable()->index();
                // draft → loading → dispatched → completed  (or cancelled)
                $table->string('status', 16)->default('draft')->index();
                $table->timestamp('planned_departure_at')->nullable();
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('dispatched_by')->nullable();
                $table->string('notes', 1000)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_parcels')) {
            Schema::create('order_parcels', function (Blueprint $table) {
                $table->id();
                $table->string('order_id', 191)->index();
                $table->string('code', 32)->unique();       // GZ7K3M9Q-2
                $table->unsignedSmallInteger('sequence');    // 2 (of N)
                $table->string('package_type', 16)->default('bag'); // bag | carton | cooler | crate | other
                $table->text('handling')->nullable();       // JSON: fragile, keep_upright, perishable, frozen, heavy, liquid
                $table->decimal('weight_kg', 8, 2)->nullable();
                $table->string('contents', 500)->nullable();
                // packed → loaded → out_for_delivery → delivered | returned
                $table->string('status', 20)->default('packed')->index();
                $table->unsignedBigInteger('dispatch_batch_id')->nullable()->index();
                $table->timestamp('labelled_at')->nullable();
                $table->timestamp('loaded_at')->nullable();
                $table->unsignedBigInteger('loaded_by')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
                $table->unique(['order_id', 'sequence']);
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            $add = fn (string $c) => !Schema::hasColumn('orders', $c);

            if ($add('stock_location_id'))  $table->unsignedBigInteger('stock_location_id')->nullable()->index(); // fulfilling branch
            if ($add('delivery_zone_id'))   $table->unsignedBigInteger('delivery_zone_id')->nullable()->index();
            // unpacked → packing → packed → verified
            if ($add('packing_status'))     $table->string('packing_status', 16)->default('unpacked')->index();
            if ($add('package_count'))      $table->unsignedSmallInteger('package_count')->nullable();
            if ($add('packing_notes'))      $table->string('packing_notes', 1000)->nullable();
            if ($add('packed_by'))          $table->unsignedBigInteger('packed_by')->nullable();
            if ($add('packed_at'))          $table->timestamp('packed_at')->nullable();
            if ($add('checked_by'))         $table->unsignedBigInteger('checked_by')->nullable();
            if ($add('checked_at'))         $table->timestamp('checked_at')->nullable();
            if ($add('dispatch_batch_id'))  $table->unsignedBigInteger('dispatch_batch_id')->nullable()->index();
            if ($add('stop_sequence'))      $table->unsignedSmallInteger('stop_sequence')->nullable();
            // assigned → out_for_delivery → delivered | failed | returned
            if ($add('dispatch_status'))    $table->string('dispatch_status', 20)->nullable()->index();
            if ($add('dispatch_note'))      $table->string('dispatch_note', 500)->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'picked_quantity')) {
                $table->unsignedInteger('picked_quantity')->nullable();
            }
            if (!Schema::hasColumn('order_items', 'substitution')) {
                $table->string('substitution', 500)->nullable(); // "Replaced with Peak 400g (customer agreed)"
            }
        });

        // Permissions
        if (class_exists(\Spatie\Permission\Models\Permission::class) && Schema::hasTable('permissions')) {
            $hasMeta = Schema::hasColumn('permissions', 'title');
            $perms = [
                'Pack orders'     => ['Pack orders', 'Record parcels, print labels and packing slips, sign off packing.'],
                'View dispatch'   => ['View dispatch', 'See the dispatch board, batches and manifests.'],
                'Manage dispatch' => ['Manage dispatch', 'Create delivery batches, assign riders, load and dispatch parcels, record deliveries.'],
                'Manage riders'   => ['Manage riders & zones', 'Add delivery riders and delivery zones.'],
            ];
            $role = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
            foreach ($perms as $name => [$title, $desc]) {
                $perm = \Spatie\Permission\Models\Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    $hasMeta ? ['title' => $title, 'description' => $desc] : []
                );
                if ($role && !$role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            foreach (['picked_quantity', 'substitution'] as $c) {
                if (Schema::hasColumn('order_items', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('orders', function (Blueprint $table) {
            foreach (['stock_location_id', 'delivery_zone_id', 'packing_status', 'package_count', 'packing_notes', 'packed_by', 'packed_at',
                      'checked_by', 'checked_at', 'dispatch_batch_id', 'stop_sequence', 'dispatch_status', 'dispatch_note'] as $c) {
                if (Schema::hasColumn('orders', $c)) $table->dropColumn($c);
            }
        });
        Schema::dropIfExists('order_parcels');
        Schema::dropIfExists('dispatch_batches');
        Schema::dropIfExists('delivery_riders');
        Schema::dropIfExists('delivery_zones');
    }
};
