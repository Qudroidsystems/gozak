<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BVN verification for Gozak Credit applications through Paystack's
 * Customer Validation API: BVN + bank account + first/last name are matched
 * by the bank (asynchronous, result arrives by webhook).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_applications', function (Blueprint $t) {
            $t->string('bank_code', 20)->nullable()->after('bvn_last4');
            $t->string('bank_name', 120)->nullable()->after('bank_code');
            $t->text('account_number')->nullable()->after('bank_name');       // encrypted
            $t->string('account_last4', 4)->nullable()->after('account_number');
            $t->string('account_name', 150)->nullable()->after('account_last4'); // as returned by the bank
            $t->string('paystack_customer_code', 40)->nullable()->index()->after('account_name');
            $t->string('bvn_status', 20)->default('unverified')->after('paystack_customer_code'); // unverified | pending | verified | failed
            $t->string('bvn_failure_reason', 255)->nullable()->after('bvn_status');
            $t->timestamp('bvn_checked_at')->nullable()->after('bvn_failure_reason');
        });

        if (!Schema::hasColumn('users', 'paystack_customer_code')) {
            Schema::table('users', function (Blueprint $t) {
                $t->string('paystack_customer_code', 40)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('credit_applications', function (Blueprint $t) {
            $t->dropColumn(['bank_code', 'bank_name', 'account_number', 'account_last4', 'account_name', 'paystack_customer_code', 'bvn_status', 'bvn_failure_reason', 'bvn_checked_at']);
        });
        if (Schema::hasColumn('users', 'paystack_customer_code')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn('paystack_customer_code'));
        }
    }
};
