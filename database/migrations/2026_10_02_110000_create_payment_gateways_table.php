<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payment gateways admins can configure (Settings › Payment Gateways).
 * - Paystack is created switched ON with the keys currently in .env, so
 *   checkout keeps working exactly as before.
 * - OPay is created switched OFF until an admin enters its keys.
 * - Adds the "Manage payment gateways" permission and gives it to Super Admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('provider_key')->unique();
                $table->text('secret_key')->nullable();
                $table->text('public_key')->nullable();
                $table->enum('mode', ['sandbox', 'live'])->default('sandbox');
                $table->decimal('fee_percentage', 5, 2)->default(0);
                $table->decimal('fee_fixed', 10, 2)->default(0);
                $table->json('config')->nullable();
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        $now = now();

        // Paystack — carry over the .env keys
        if (!DB::table('payment_gateways')->where('provider_key', 'paystack')->exists()) {
            $secret = (string) config('services.paystack.secret_key');
            $public = (string) config('services.paystack.public_key');
            $set    = str_starts_with($secret, 'sk_live_') ? 'live' : 'test';
            $creds  = [];
            if ($secret !== '') $creds[$set]['secret_key'] = 'enc:' . Crypt::encryptString($secret);
            if ($public !== '') $creds[$set]['public_key'] = $public;

            DB::table('payment_gateways')->insert([
                'name'         => 'Paystack',
                'provider_key' => 'paystack',
                'public_key'   => $public ?: null,
                'mode'         => $set === 'live' ? 'live' : 'sandbox',
                'config'       => json_encode(['credentials' => (object) $creds]),
                'is_active'    => $secret !== '',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        if (!DB::table('payment_gateways')->where('provider_key', 'opay')->exists()) {
            DB::table('payment_gateways')->insert([
                'name'         => 'OPay',
                'provider_key' => 'opay',
                'mode'         => 'sandbox',
                'config'       => json_encode(['credentials' => (object) []]),
                'is_active'    => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // Permission
        if (Schema::hasTable('permissions') && class_exists(\Spatie\Permission\Models\Permission::class)) {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'Manage payment gateways', 'guard_name' => 'web'],
                ['title' => 'Payment gateways', 'description' => 'Enter Paystack / OPay keys and switch gateways on or off.']
            );
            $role = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
            if ($role && !$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            \Spatie\Permission\Models\Permission::where('name', 'Manage payment gateways')->delete();
        }
    }
};
