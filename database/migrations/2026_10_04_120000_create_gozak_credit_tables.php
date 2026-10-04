<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Gozak Credit — buy now, pay at the end of the month.
 *
 *  credit_settings            one row: master switch, audience, pricing, billing calendar, collections policy
 *  credit_applications        customer applications + system risk score + admin decision
 *  credit_accounts            one per approved customer: limit, balance, status, virtual card
 *  credit_mandates            how we collect: Paystack direct-debit mandate (bank) and/or reusable card
 *  credit_ledger_entries      every money movement (purchase, fee, payment, refund, adjustment) — the source of truth
 *  credit_statements          monthly bills: what is owed and by when
 *  credit_collection_attempts every automatic/manual debit attempt and its result
 *  credit_audit_logs          who did what and when (approvals, limit changes, freezes, waivers…)
 *
 * Amounts are naira with 2 decimals. Balance = sum(ledger debit) − sum(ledger credit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_settings', function (Blueprint $t) {
            $t->id();
            $t->boolean('enabled')->default(false);                 // master switch
            $t->string('audience', 20)->default('pilot');          // pilot | everyone
            $t->text('pilot_emails')->nullable();                  // one per line; staff are always pilots
            $t->decimal('markup_percent', 5, 2)->default(5.00);    // added to every credit purchase
            $t->decimal('min_limit', 12, 2)->default(10000);
            $t->decimal('max_limit', 12, 2)->default(500000);
            $t->unsignedTinyInteger('statement_day')->default(25); // cycle closes on this day of the month
            $t->unsignedTinyInteger('due_day')->default(28);       // debit date (same or next month)
            $t->unsignedTinyInteger('grace_days')->default(3);     // late fee after due date + grace
            $t->decimal('late_fee_flat', 10, 2)->default(1000);
            $t->decimal('late_fee_percent', 5, 2)->default(2.00);  // of the overdue amount (the higher wins)
            $t->decimal('late_fee_cap', 10, 2)->default(10000);
            $t->unsignedTinyInteger('max_attempts')->default(6);   // automatic debit attempts per statement
            $t->unsignedSmallInteger('retry_hours')->default(24);
            $t->boolean('card_fallback')->default(true);
            $t->boolean('require_mandate')->default(true);         // must link a bank mandate before spending
            $t->string('reminder_days', 30)->default('5,2,0');     // days before due date to remind
            $t->unsignedTinyInteger('suspend_after_days')->default(1); // overdue days before card is suspended
            $t->string('terms_version', 20)->default('2026-10');
            $t->text('terms_text')->nullable();
            $t->timestamps();
        });
        DB::table('credit_settings')->insert(['created_at' => now(), 'updated_at' => now()]);

        Schema::create('credit_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('status', 20)->default('pending');          // pending | approved | rejected | cancelled
            $t->string('full_name', 150);
            $t->string('phone', 30);
            $t->date('date_of_birth')->nullable();
            $t->text('bvn')->nullable();                           // encrypted
            $t->string('bvn_last4', 4)->nullable();
            $t->string('employment_status', 30);                   // employed | self_employed | business_owner | student | other
            $t->string('employer', 150)->nullable();
            $t->string('monthly_income', 30);                      // income band
            $t->string('address', 255)->nullable();
            $t->string('state', 60)->nullable();
            $t->decimal('requested_limit', 12, 2);
            $t->decimal('suggested_limit', 12, 2)->default(0);
            $t->unsignedTinyInteger('risk_score')->default(0);     // 0 (risky) … 100 (safe)
            $t->json('risk_factors')->nullable();
            $t->decimal('approved_limit', 12, 2)->nullable();
            $t->string('decision_note', 500)->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->string('terms_version', 20);
            $t->timestamp('consented_at');
            $t->string('consent_ip', 45)->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });

        Schema::create('credit_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $t->foreignId('application_id')->nullable()->constrained('credit_applications')->nullOnDelete();
            $t->string('status', 20)->default('pending_mandate');  // pending_mandate | active | suspended | frozen | closed
            $t->string('status_reason', 255)->nullable();
            $t->decimal('credit_limit', 12, 2);
            $t->decimal('balance', 12, 2)->default(0);             // cached: everything owed
            $t->decimal('unbilled', 12, 2)->default(0);            // cached: spent since last statement
            $t->decimal('markup_percent', 5, 2)->nullable();       // override of the global markup
            $t->string('card_number', 19);                         // display number, not a payment card
            $t->string('card_name', 60);
            $t->date('card_expires_on');
            $t->unsignedSmallInteger('on_time_payments')->default(0);
            $t->unsignedSmallInteger('late_payments')->default(0);
            $t->timestamp('activated_at')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->index('status');
        });

        Schema::create('credit_mandates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credit_account_id')->constrained('credit_accounts')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('type', 20);                                // direct_debit | card
            $t->string('status', 20)->default('pending');          // pending | active | revoked | failed
            $t->string('reference', 100)->nullable()->index();
            $t->text('authorization_code')->nullable();            // encrypted
            $t->string('email', 150);                              // the email Paystack bound the authorization to
            $t->string('bank_name', 120)->nullable();
            $t->string('account_name', 150)->nullable();
            $t->string('last4', 4)->nullable();
            $t->string('card_brand', 30)->nullable();
            $t->string('expiry', 7)->nullable();                   // MM/YYYY for cards
            $t->string('signature', 100)->nullable();
            $t->boolean('is_primary')->default(false);
            $t->unsignedSmallInteger('failures')->default(0);
            $t->timestamp('activated_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->json('gateway_data')->nullable();
            $t->timestamps();
            $t->index(['credit_account_id', 'status']);
        });

        Schema::create('credit_statements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credit_account_id')->constrained('credit_accounts')->cascadeOnDelete();
            $t->string('number', 30)->unique();
            $t->date('period_start');
            $t->date('period_end');
            $t->decimal('opening_balance', 12, 2)->default(0);
            $t->decimal('purchases', 12, 2)->default(0);
            $t->decimal('fees', 12, 2)->default(0);
            $t->decimal('payments', 12, 2)->default(0);
            $t->decimal('credits', 12, 2)->default(0);
            $t->decimal('amount_due', 12, 2);
            $t->decimal('amount_paid', 12, 2)->default(0);
            $t->date('due_date');
            $t->string('status', 20)->default('issued');           // issued | paid | partially_paid | overdue | waived
            $t->timestamp('paid_at')->nullable();
            $t->timestamp('late_fee_applied_at')->nullable();
            $t->timestamp('next_attempt_at')->nullable();
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->string('reminders_sent', 40)->nullable();          // e.g. "5,2,0"
            $t->timestamps();
            $t->index(['status', 'due_date']);
            $t->index(['status', 'next_attempt_at']);
        });

        Schema::create('credit_ledger_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credit_account_id')->constrained('credit_accounts')->cascadeOnDelete();
            $t->foreignId('statement_id')->nullable()->constrained('credit_statements')->nullOnDelete();
            $t->string('type', 20);                                // purchase | fee | late_fee | payment | refund | adjustment | reversal
            $t->string('direction', 6);                            // debit (customer owes more) | credit (owes less)
            $t->decimal('amount', 12, 2);
            $t->decimal('balance_after', 12, 2);
            $t->string('order_id', 64)->nullable()->index();
            $t->string('reference', 100)->nullable()->unique();
            $t->string('description', 255);
            $t->json('meta')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['credit_account_id', 'statement_id']);
            $t->index(['credit_account_id', 'created_at']);
        });

        Schema::create('credit_collection_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credit_account_id')->constrained('credit_accounts')->cascadeOnDelete();
            $t->foreignId('statement_id')->nullable()->constrained('credit_statements')->nullOnDelete();
            $t->foreignId('mandate_id')->nullable()->constrained('credit_mandates')->nullOnDelete();
            $t->string('channel', 20);                             // direct_debit | card | checkout | manual
            $t->string('trigger', 20)->default('auto');            // auto | admin | customer
            $t->decimal('amount', 12, 2);
            $t->string('reference', 100)->unique();
            $t->string('status', 20)->default('pending');          // pending | processing | success | failed
            $t->string('failure_reason', 255)->nullable();
            $t->json('gateway_response')->nullable();
            $t->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });

        Schema::create('credit_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('credit_account_id')->nullable()->constrained('credit_accounts')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // the customer
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete(); // who did it (null = system)
            $t->string('action', 60);
            $t->string('summary', 255);
            $t->json('data')->nullable();
            $t->timestamps();
            $t->index(['credit_account_id', 'created_at']);
        });

        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'credit_fee')) {
            Schema::table('orders', function (Blueprint $t) {
                $t->decimal('credit_fee', 12, 2)->nullable()->after('total_amount');
            });
        }

        $this->permissions();
    }

    protected function permissions(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }
        $defs = [
            'View credit'               => 'See Gozak Credit dashboards, accounts, statements and collections.',
            'Review credit applications'=> 'Approve or reject credit applications and set limits.',
            'Manage credit'             => 'Change limits, freeze/close accounts, record payments, waive fees, trigger debits.',
            'Manage credit settings'    => 'Turn Gozak Credit on/off and change pricing, billing dates and collection rules.',
        ];
        $perms = [];
        foreach ($defs as $name => $desc) {
            $perms[] = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['title' => $name, 'description' => $desc]);
        }
        foreach (['Super Admin', 'Admin'] as $r) {
            if ($role = Role::where('name', $r)->first()) {
                $role->givePermissionTo($perms);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['credit_audit_logs', 'credit_collection_attempts', 'credit_ledger_entries', 'credit_statements', 'credit_mandates', 'credit_accounts', 'credit_applications', 'credit_settings'] as $table) {
            Schema::dropIfExists($table);
        }
        if (Schema::hasColumn('orders', 'credit_fee')) {
            Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('credit_fee'));
        }
        Permission::whereIn('name', ['View credit', 'Review credit applications', 'Manage credit', 'Manage credit settings'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
