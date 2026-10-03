<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refunds can now go back through the payment gateway (Paystack) or be
 * recorded as done manually (cash, bank transfer, OPay dashboard).
 * Adds the gateway details and lets status be pending | processing |
 * processed | failed | rejected. Also adds the "Refund order" permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            if (!Schema::hasColumn('refunds', 'method')) {
                $table->string('method', 20)->default('manual')->after('reason');          // gateway | manual
            }
            if (!Schema::hasColumn('refunds', 'gateway')) {
                $table->string('gateway', 30)->nullable()->after('method');               // paystack | opay | cash | bank_transfer
            }
            if (!Schema::hasColumn('refunds', 'transaction_reference')) {
                $table->string('transaction_reference')->nullable()->after('gateway');    // original payment reference
            }
            if (!Schema::hasColumn('refunds', 'gateway_refund_id')) {
                $table->string('gateway_refund_id')->nullable()->index()->after('transaction_reference');
            }
            if (!Schema::hasColumn('refunds', 'manual_reference')) {
                $table->string('manual_reference')->nullable()->after('gateway_refund_id'); // bank transfer ref etc.
            }
            if (!Schema::hasColumn('refunds', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('manual_reference');
            }
            if (!Schema::hasColumn('refunds', 'failure_reason')) {
                $table->string('failure_reason')->nullable()->after('gateway_response');
            }
        });

        // enum → string so we can store "processing" and "failed"
        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE refunds MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }

        if (class_exists(\Spatie\Permission\Models\Permission::class) && Schema::hasTable('permissions')) {
            $perm = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'Refund order', 'guard_name' => 'web'],
                ['title' => 'Refund orders', 'description' => 'Send money back to customers (Paystack) or record manual refunds.']
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
        Schema::table('refunds', function (Blueprint $table) {
            foreach (['method', 'gateway', 'transaction_reference', 'gateway_refund_id', 'manual_reference', 'gateway_response', 'failure_reason'] as $c) {
                if (Schema::hasColumn('refunds', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
