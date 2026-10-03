<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Customers' Nigerian bank accounts — used to pay refunds back by transfer
 * (OPay orders, partial refunds, failed card reversals).
 *
 * The full account number is stored encrypted (APP_KEY); `account_hash`
 * lets us block duplicates without decrypting, and `account_last4` is what
 * lists show. The account name always comes from Paystack's resolve API,
 * never from what the customer typed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('bank_code', 20);
            $table->string('bank_name', 120);
            $table->text('account_number');                 // encrypted
            $table->string('account_hash', 64);
            $table->string('account_last4', 4);
            $table->string('account_name', 150);
            $table->boolean('name_matches')->default(false); // resolved name looks like the user's name
            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'account_hash']);
            $table->index(['user_id', 'is_default']);
        });

        if (Schema::hasTable('permissions')) {
            $perm = Permission::firstOrCreate(
                ['name' => 'Manage own bank accounts', 'guard_name' => 'web'],
                ['title' => 'Manage own bank accounts', 'description' => 'Add and remove the bank accounts their refunds are paid into.']
            );
            if ($role = Role::where('name', 'App Users')->first()) {
                $role->givePermissionTo($perm);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bank_accounts');
        if (Schema::hasTable('permissions')) {
            Permission::where('name', 'Manage own bank accounts')->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
