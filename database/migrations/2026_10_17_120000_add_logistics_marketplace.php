<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * GozakMart as a merchant on the logistics marketplace:
 *  - addresses get a map pin (needed to price a courier)
 *  - logistics_settings: API key, webhook secret, what the marketplace says about us
 *  - order_shipments: the courier the customer chose at checkout and its booking / tracking
 *  - logistics_webhook_events: signed delivery updates received (idempotency + audit)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('addresses', 'latitude'))  $table->decimal('latitude', 10, 7)->nullable();
            if (!Schema::hasColumn('addresses', 'longitude')) $table->decimal('longitude', 10, 7)->nullable();
        });

        if (!Schema::hasTable('logistics_settings')) {
            Schema::create('logistics_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('enabled')->default(false);
                $table->string('base_url')->nullable();            // e.g. https://logistics.example.com/api/v1/partner
                $table->text('api_key')->nullable();               // encrypted
                $table->text('webhook_secret')->nullable();        // encrypted
                $table->string('environment', 8)->nullable();      // test | live (from the key / /me)
                $table->string('merchant_code', 40)->nullable();
                $table->string('merchant_name')->nullable();
                $table->string('merchant_status', 20)->nullable(); // verified | pending | …
                $table->string('marketplace_name')->nullable();
                $table->string('marketplace_badge')->nullable();
                $table->string('marketplace_logo')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->string('last_error', 500)->nullable();
                $table->unsignedBigInteger('pickup_stock_location_id')->nullable();
                $table->unsignedInteger('default_weight_g')->default(2000);
                $table->unsignedTinyInteger('max_price_increase_percent')->default(10); // absorbed if a quote expires and goes up
                $table->boolean('allow_receiver_pays')->default(true);
                $table->boolean('own_delivery_enabled')->default(true);
                $table->string('own_delivery_label', 80)->default('GozakMart Delivery');
                $table->string('own_delivery_eta', 80)->default('Same day');
                $table->timestamps();
            });
            DB::table('logistics_settings')->insert(['created_at' => now(), 'updated_at' => now()]);
        }

        if (!Schema::hasTable('order_shipments')) {
            Schema::create('order_shipments', function (Blueprint $table) {
                $table->id();
                $table->string('order_id', 191)->unique();
                $table->string('carrier', 20)->default('logistics');
                $table->string('option_id', 40)->nullable();
                $table->string('service_name', 80)->nullable();
                $table->string('provider_id', 40)->nullable();
                $table->string('provider_name', 120)->nullable();
                $table->string('provider_logo')->nullable();
                $table->decimal('provider_rating', 3, 1)->nullable();
                $table->string('marketplace_name', 120)->nullable();
                $table->string('eta_label', 60)->nullable();
                $table->decimal('quoted_price', 12, 2)->default(0);  // naira, what the customer saw
                $table->decimal('price', 12, 2)->nullable();         // naira, what the marketplace booked
                $table->string('fee_payer', 12)->default('merchant'); // merchant (paid in the order) | receiver (cash to rider)
                // selected → booked → assigned → picked_up → delivered   (failed | cancelled | delivery_failed | returned)
                $table->string('status', 20)->default('selected')->index();
                $table->string('external_status', 30)->nullable();
                $table->string('agreement', 40)->nullable();
                $table->string('external_order_number', 40)->nullable();
                $table->string('tracking_code', 40)->nullable();
                $table->string('tracking_url')->nullable();
                $table->string('payment_url')->nullable();
                $table->timestamp('booked_at')->nullable();
                $table->timestamp('picked_up_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->string('last_error', 500)->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('logistics_webhook_events')) {
            Schema::create('logistics_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id', 60)->unique();
                $table->string('type', 40);
                $table->string('order_id', 191)->nullable()->index();
                $table->json('payload')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_webhook_events');
        Schema::dropIfExists('order_shipments');
        Schema::dropIfExists('logistics_settings');
        Schema::table('addresses', function (Blueprint $table) {
            foreach (['latitude', 'longitude'] as $c) {
                if (Schema::hasColumn('addresses', $c)) $table->dropColumn($c);
            }
        });
    }
};
