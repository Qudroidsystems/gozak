<?php

namespace App\Services\Credit;

use App\Models\Credit\CreditAccount;
use App\Models\Credit\CreditLedgerEntry;
use App\Models\Credit\CreditSetting;
use App\Models\Credit\CreditStatement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The automatic monthly cycle. `php artisan credit:run` (hourly) calls run();
 * every step is idempotent, so running it more often is safe.
 *
 *  1. issue statements the day after each cycle closes
 *  2. remind customers N days before the due date
 *  3. debit due statements (bank mandate first, card backup), retry on failure
 *  4. mark overdue, add one late fee after the grace period
 *  5. suspend spending while anything is overdue
 *  6. re-check debits/mandates whose webhooks didn't arrive
 */
class CreditBillingService
{
    public function __construct(protected CreditService $credit, protected CreditGateway $gateway)
    {
    }

    protected function s(): CreditSetting
    {
        return CreditSetting::current();
    }

    protected function today(): Carbon
    {
        return now(CreditSetting::TZ)->startOfDay();
    }

    public function run(): array
    {
        $out = [];
        foreach (['issueStatements', 'sendReminders', 'markOverdue', 'applyLateFees', 'suspendOverdue', 'collectDue', 'sweep', 'sweepBvn'] as $step) {
            try {
                $out[$step] = $this->{$step}();
            } catch (\Throwable $e) {
                Log::error("Credit billing step {$step} failed: " . $e->getMessage());
                $out[$step] = 'error: ' . $e->getMessage();
            }
        }
        return $out;
    }

    /** Close the cycle for every account with spending since its last statement. */
    public function issueStatements(?Carbon $asOf = null): int
    {
        $today = ($asOf ?? $this->today())->copy()->startOfDay();
        // The most recent closing date that has fully passed.
        $close = $this->s()->statementDateFor($today->copy()->subDay());
        if ($close->gte($today)) {
            $close = $this->s()->statementDateFor($today->copy()->subMonthNoOverflow()->subDay());
        }
        $n = 0;
        CreditAccount::where('status', '!=', CreditAccount::CLOSED)
            ->where(fn ($q) => $q->where('unbilled', '!=', 0)->orWhereHas('ledger', fn ($l) => $l->whereNull('statement_id')->where('type', '!=', 'payment')))
            ->orderBy('id')->chunkById(200, function ($accounts) use ($close, &$n) {
                foreach ($accounts as $a) {
                    try {
                        if ($this->issueFor($a, $close)) {
                            $n++;
                        }
                    } catch (\Throwable $e) {
                        Log::error("Credit statement for account {$a->id} failed: " . $e->getMessage());
                    }
                }
            });
        return $n;
    }

    public function issueFor(CreditAccount $account, Carbon $close): ?CreditStatement
    {
        $periodEnd = $close->copy()->endOfDay();
        if (CreditStatement::where('credit_account_id', $account->id)->whereDate('period_end', $close->toDateString())->exists()) {
            return null;
        }

        $st = $this->credit->locked($account->id, function (CreditAccount $a) use ($close, $periodEnd) {
            $unbilled = CreditLedgerEntry::where('credit_account_id', $a->id)->whereNull('statement_id')
                ->whereNotIn('type', ['payment', 'late_fee'])->where('created_at', '<=', $periodEnd->copy()->setTimezone(config('app.timezone')))->get();
            if ($unbilled->isEmpty()) {
                return null;
            }
            // A credit partly used against an older bill only counts with the part that reduced unbilled.
            $unbilledPart = fn ($e) => isset($e->meta['statement_allocations']) ? -((float) ($e->meta['unbilled_part'] ?? 0)) : $e->signed();
            $after = (float) CreditLedgerEntry::where('credit_account_id', $a->id)->whereNull('statement_id')
                ->whereNotIn('type', ['payment', 'late_fee'])->where('created_at', '>', $periodEnd->copy()->setTimezone(config('app.timezone')))
                ->get()->sum($unbilledPart);

            $net = round($a->unbilled - $after, 2); // includes early payments already taken off unbilled
            $due = max(0, $net);
            $last = CreditStatement::where('credit_account_id', $a->id)->latest('period_end')->first();

            $purchases = $unbilled->where('type', 'purchase')->sum('amount');
            $fees = $unbilled->where('type', 'fee')->sum('amount') + $unbilled->where('type', 'adjustment')->where('direction', 'debit')->sum('amount');
            $credits = -$unbilled->where('direction', 'credit')->sum($unbilledPart);
            $earlyPaid = round($purchases + $fees - $credits - $net, 2);

            $st = CreditStatement::create([
                'credit_account_id' => $a->id,
                'number'            => 'GZC-' . $close->format('Ymd') . '-' . str_pad((string) $a->id, 5, '0', STR_PAD_LEFT),
                'period_start'      => $last ? $last->period_end->copy()->addDay()->toDateString() : $unbilled->min('created_at')->setTimezone(CreditSetting::TZ)->toDateString(),
                'period_end'        => $close->toDateString(),
                'opening_balance'   => round($a->balance - $after - $net, 2), // older bills still unpaid
                'purchases'         => round($purchases, 2),
                'fees'              => round($fees, 2),
                'payments'          => max(0, $earlyPaid),
                'credits'           => round($credits, 2),
                'amount_due'        => round($due, 2),
                'amount_paid'       => 0,
                'due_date'          => $this->s()->dueDateFor($close)->toDateString(),
                'status'            => $due > 0 ? 'issued' : 'paid',
                'paid_at'           => $due > 0 ? null : now(),
            ]);
            CreditLedgerEntry::whereIn('id', $unbilled->pluck('id'))->update(['statement_id' => $st->id]);
            // Leftover prepaid credit (net < 0) carries over to the next cycle.
            $a->unbilled = round($after + min(0, $net), 2);
            $a->save();
            return $st;
        });

        if ($st && $st->amount_due > 0) {
            $this->credit->audit($account, $account->user_id, null, 'statement.issued', $st->number . ': ₦' . number_format($st->amount_due, 2) . ' due ' . $st->due_date->format('j M'));
            $this->credit->notify($account->user, 'Your Gozak Credit statement is ready', '₦' . number_format($st->amount_due, 2) . ' will be debited automatically on ' . $st->due_date->format('j M') . '. You can also pay early in the app.', ['screen' => 'credit_statement', 'statement_id' => $st->id]);
        }
        return $st;
    }

    public function sendReminders(): int
    {
        $today = $this->today();
        $n = 0;
        $days = $this->s()->reminderDays();
        CreditStatement::whereIn('status', ['issued', 'partially_paid'])->where('amount_due', '>', 0)
            ->whereDate('due_date', '>=', $today->toDateString())->with('account.user')->chunkById(200, function ($rows) use ($today, $days, &$n) {
                foreach ($rows as $st) {
                    $left = (int) $today->diffInDays($st->due_date->copy()->startOfDay(), false);
                    $sent = array_filter(explode(',', (string) $st->reminders_sent), 'strlen');
                    if (!in_array($left, $days, true) || in_array((string) $left, $sent, true)) {
                        continue;
                    }
                    $amount = '₦' . number_format($st->outstanding(), 2);
                    $when = $left === 0 ? 'today' : ($left === 1 ? 'tomorrow' : 'in ' . $left . ' days');
                    $this->credit->notify($st->account->user, 'Gozak Credit payment ' . ($left === 0 ? 'due today' : 'reminder'), "{$amount} will be debited {$when}. Please keep enough money in your account.", ['screen' => 'credit']);
                    $sent[] = (string) $left;
                    $st->update(['reminders_sent' => implode(',', $sent)]);
                    $n++;
                }
            });
        return $n;
    }

    public function markOverdue(): int
    {
        return CreditStatement::whereIn('status', ['issued', 'partially_paid'])
            ->whereDate('due_date', '<', $this->today()->toDateString())
            ->whereColumn('amount_paid', '<', 'amount_due')
            ->update(['status' => 'overdue']);
    }

    public function applyLateFees(): int
    {
        $s = $this->s();
        $cutoff = $this->today()->subDays($s->grace_days)->toDateString();
        $n = 0;
        CreditStatement::where('status', 'overdue')->whereNull('late_fee_applied_at')->whereDate('due_date', '<', $cutoff)
            ->with('account.user')->chunkById(100, function ($rows) use ($s, &$n) {
                foreach ($rows as $st) {
                    $fee = $s->lateFeeFor($st->outstanding());
                    if ($fee <= 0) {
                        $st->update(['late_fee_applied_at' => now()]);
                        continue;
                    }
                    $this->credit->locked($st->credit_account_id, function (CreditAccount $a) use ($st, $fee) {
                        $st = CreditStatement::whereKey($st->id)->lockForUpdate()->first();
                        if ($st->late_fee_applied_at) {
                            return;
                        }
                        $this->credit->post($a, 'late_fee', 'debit', $fee, 'Late fee · ' . $st->number, ['statement_id' => $st->id, 'reference' => 'LATE-' . $st->id]);
                        $st->update(['amount_due' => round($st->amount_due + $fee, 2), 'fees' => round($st->fees + $fee, 2), 'late_fee_applied_at' => now()]);
                    });
                    $this->credit->audit($st->account, $st->account->user_id, null, 'late_fee.applied', '₦' . number_format($fee, 2) . ' late fee on ' . $st->number);
                    $this->credit->notify($st->account->user, 'Gozak Credit payment overdue', 'A late fee of ₦' . number_format($fee, 2) . ' was added. Pay ₦' . number_format($st->fresh()->outstanding(), 2) . ' in the app to restore your credit.', ['screen' => 'credit']);
                    $n++;
                }
            });
        return $n;
    }

    public function suspendOverdue(): int
    {
        $cutoff = $this->today()->subDays($this->s()->suspend_after_days)->toDateString();
        $n = 0;
        CreditAccount::where('status', CreditAccount::ACTIVE)
            ->whereHas('statements', fn ($q) => $q->where('status', 'overdue')->whereDate('due_date', '<=', $cutoff))
            ->lazyById(200)
            ->each(function (CreditAccount $a) use (&$n) {
                $a->update(['status' => CreditAccount::SUSPENDED, 'status_reason' => 'Overdue payment']);
                $this->credit->audit($a, $a->user_id, null, 'status.suspended', 'Suspended automatically: overdue payment');
                $this->credit->notify($a->user, 'Gozak Credit paused', 'Your Gozak Credit is paused until your overdue balance is paid.', ['screen' => 'credit']);
                $n++;
            });
        return $n;
    }

    /**
     * Debit due statements. First try on the due date (from 8am Lagos), then
     * retry every `retry_hours`, alternating bank mandate and card backup,
     * up to `max_attempts`. Runs only 8am–8pm so customers aren't debited at night.
     */
    public function collectDue(): int
    {
        $s = $this->s();
        $now = now(CreditSetting::TZ);
        if ($now->hour < 8 || $now->hour >= 20) {
            return 0;
        }
        $n = 0;
        CreditStatement::whereIn('status', CreditStatement::OPEN)
            ->whereDate('due_date', '<=', $now->toDateString())
            ->where('attempts', '<', $s->max_attempts)
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->whereHas('account', fn ($q) => $q->whereIn('status', [CreditAccount::ACTIVE, CreditAccount::SUSPENDED, CreditAccount::FROZEN, CreditAccount::PENDING_MANDATE]))
            ->with('account')->orderBy('due_date')->limit(300)->get()
            ->each(function (CreditStatement $st) use ($s, &$n) {
                if ($st->outstanding() <= 0) {
                    return;
                }
                $last = $st->collectionAttempts()->whereIn('channel', ['direct_debit', 'card'])->first();
                $prefer = null;
                if ($last && $last->status === 'failed' && $s->card_fallback) {
                    $prefer = $last->channel === 'direct_debit' ? 'card' : 'direct_debit';
                }
                try {
                    $this->gateway->collect($st, 'auto', null, $prefer);
                    $n++;
                } catch (\Throwable $e) {
                    $st->update(['next_attempt_at' => now()->addHours(max(1, $s->retry_hours))]);
                    Log::info("Credit collect skipped for statement {$st->id}: " . $e->getMessage());
                }
            });
        return $n;
    }

    public function sweepBvn(): int
    {
        return app(CreditBvnVerifier::class)->sweep();
    }

    public function sweep(): int
    {
        return $this->gateway->sweepProcessing();
    }
}
