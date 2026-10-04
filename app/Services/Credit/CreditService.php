<?php

namespace App\Services\Credit;

use App\Models\Credit\CreditAccount;
use App\Models\Credit\CreditApplication;
use App\Models\Credit\CreditAuditLog;
use App\Models\Credit\CreditLedgerEntry;
use App\Models\Credit\CreditSetting;
use App\Models\Credit\CreditStatement;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BarcodeService;
use App\Services\FcmService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Gozak Credit — accounts, applications and every money movement.
 *
 * The ledger (credit_ledger_entries) is the source of truth; credit_accounts
 * .balance / .unbilled are caches updated in the same DB transaction, with
 * the account row locked, so two requests can never overspend a limit.
 */
class CreditService
{
    public const GATEWAY = 'gozak_credit';

    public function settings(): CreditSetting
    {
        return CreditSetting::current();
    }

    // ── Who can see it ───────────────────────────────────────────────────────

    public function isStaff(User $user): bool
    {
        return $user->roles()->whereNotIn('name', ['App Users', 'customer', 'user'])->exists();
    }

    /** Is Gozak Credit switched on for this customer (master switch + audience)? */
    public function isOfferedTo(User $user): bool
    {
        $s = $this->settings();
        if (!$s->enabled) {
            return false;
        }
        if ($s->audience === 'everyone') {
            return true;
        }
        return $this->isStaff($user) || in_array(strtolower((string) $user->email), $s->pilotEmails(), true);
    }

    public function accountFor(User $user): ?CreditAccount
    {
        return CreditAccount::where('user_id', $user->id)->first();
    }

    // ── Applications ─────────────────────────────────────────────────────────

    /**
     * Score an applicant from what we know (order history, account age,
     * profile, stated income). Returns [score 0-100, suggested limit, factors[]].
     * The admin always makes the final decision.
     */
    public function assess(User $user, array $input): array
    {
        $s = $this->settings();
        $factors = [];
        $score = 30;

        $orders = Order::where('user_id', $user->id);
        $delivered = (clone $orders)->where('status', 'delivered')->count();
        $cancelled = (clone $orders)->where('status', 'cancelled')->count();
        $spent = (float) (clone $orders)->where('status', 'delivered')->sum('total_amount');
        $ageDays = $user->created_at ? (int) $user->created_at->diffInDays(now()) : 0;

        $add = function (int $pts, string $label) use (&$score, &$factors) {
            $score += $pts;
            $factors[] = ['points' => $pts, 'label' => $label];
        };

        $add(min(25, $delivered * 5), "{$delivered} delivered order(s)");
        if ($spent >= 200000) {
            $add(10, 'Spent ₦' . number_format($spent) . ' with us');
        } elseif ($spent >= 50000) {
            $add(5, 'Spent ₦' . number_format($spent) . ' with us');
        }
        if ($cancelled > 0) {
            $add(-min(15, $cancelled * 3), "{$cancelled} cancelled order(s)");
        }
        if ($ageDays >= 180) {
            $add(10, 'Customer for ' . intdiv($ageDays, 30) . ' months');
        } elseif ($ageDays >= 60) {
            $add(5, 'Customer for ' . intdiv($ageDays, 30) . ' months');
        } elseif ($ageDays < 14) {
            $add(-10, 'New account (' . $ageDays . ' days)');
        }
        if ($user->email_verified_at) {
            $add(5, 'Email verified');
        }
        $incomePts = ['below_100k' => 0, '100k_250k' => 5, '250k_500k' => 10, '500k_1m' => 15, 'above_1m' => 20][$input['monthly_income'] ?? ''] ?? 0;
        if ($incomePts) {
            $add($incomePts, 'Income ' . (CreditApplication::INCOME_BANDS[$input['monthly_income']] ?? ''));
        }
        if (in_array($input['employment_status'] ?? '', ['employed', 'business_owner'], true)) {
            $add(5, CreditApplication::EMPLOYMENT[$input['employment_status']]);
        }
        if (!empty($input['bvn'])) {
            $add(5, 'BVN provided');
        }
        $previous = CreditAccount::where('user_id', $user->id)->first();
        if ($previous && $previous->late_payments > 0) {
            $add(-min(30, $previous->late_payments * 10), $previous->late_payments . ' late payment(s) before');
        }

        $score = max(0, min(100, $score));

        // Suggested limit: income-based cap scaled by score, rounded down to ₦5,000.
        $incomeCap = ['below_100k' => 30000, '100k_250k' => 75000, '250k_500k' => 150000, '500k_1m' => 300000, 'above_1m' => 500000][$input['monthly_income'] ?? ''] ?? 30000;
        $suggested = $incomeCap * ($score / 100);
        if ($delivered === 0) {
            $suggested = min($suggested, 20000); // no history yet → start small
        }
        $suggested = max($s->min_limit, min($s->max_limit, floor($suggested / 5000) * 5000));
        if (!empty($input['requested_limit'])) {
            $suggested = min($suggested, max($s->min_limit, (float) $input['requested_limit']));
        }

        return [$score, round($suggested, 2), $factors];
    }

    public function apply(User $user, array $data, ?string $ip): CreditApplication
    {
        if (CreditApplication::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            throw new \RuntimeException('You already have an application under review.');
        }
        $acct = $this->accountFor($user);
        if ($acct && $acct->status !== CreditAccount::CLOSED) {
            throw new \RuntimeException('You already have a Gozak Credit account.');
        }

        [$score, $suggested, $factors] = $this->assess($user, $data);
        $bvn = preg_replace('/\D/', '', (string) ($data['bvn'] ?? ''));

        $app = CreditApplication::create([
            'user_id'           => $user->id,
            'status'            => 'pending',
            'full_name'         => $data['full_name'],
            'phone'             => $data['phone'],
            'date_of_birth'     => $data['date_of_birth'] ?? null,
            'bvn'               => $bvn ?: null,
            'bvn_last4'         => $bvn ? substr($bvn, -4) : null,
            'bank_code'         => $data['bank_code'] ?? null,
            'bank_name'         => $data['bank_name'] ?? null,
            'account_number'    => $data['account_number'] ?? null,
            'account_last4'     => isset($data['account_number']) ? substr($data['account_number'], -4) : null,
            'bvn_status'        => 'unverified',
            'employment_status' => $data['employment_status'],
            'employer'          => $data['employer'] ?? null,
            'monthly_income'    => $data['monthly_income'],
            'address'           => $data['address'] ?? null,
            'state'             => $data['state'] ?? null,
            'requested_limit'   => (float) $data['requested_limit'],
            'suggested_limit'   => $suggested,
            'risk_score'        => $score,
            'risk_factors'      => $factors,
            'terms_version'     => $this->settings()->terms_version,
            'consented_at'      => now(),
            'consent_ip'        => $ip,
        ]);

        $this->audit(null, $user->id, $user->id, 'application.submitted', 'Applied for ₦' . number_format($app->requested_limit) . ' (score ' . $score . ')', ['application_id' => $app->id]);

        // Verify the BVN with Paystack (BVN + bank account + names). Never blocks the application.
        try {
            app(CreditBvnVerifier::class)->start($app);
        } catch (\Throwable $e) {
            Log::warning('BVN check not started: ' . $e->getMessage());
        }
        return $app->fresh();
    }

    public function approve(CreditApplication $app, float $limit, User $admin, ?string $note = null): CreditAccount
    {
        if ($app->status !== 'pending') {
            throw new \RuntimeException('This application has already been decided.');
        }
        $s = $this->settings();
        $limit = round(max(0, $limit), 2);
        if ($limit < $s->min_limit || $limit > $s->max_limit) {
            throw new \InvalidArgumentException('Limit must be between ₦' . number_format($s->min_limit) . ' and ₦' . number_format($s->max_limit) . '.');
        }

        $account = DB::transaction(function () use ($app, $limit, $admin, $note) {
            $app->update(['status' => 'approved', 'approved_limit' => $limit, 'decision_note' => $note, 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
            $user = $app->user;
            $existing = CreditAccount::where('user_id', $user->id)->lockForUpdate()->first();
            $attrs = [
                'application_id'  => $app->id,
                'credit_limit'    => $limit,
                'status'          => CreditAccount::PENDING_MANDATE,
                'status_reason'   => null,
                'card_name'       => Str::upper(Str::limit($app->full_name, 26, '')),
                'card_expires_on' => now()->addYears(2)->endOfMonth()->toDateString(),
                'closed_at'       => null,
            ];
            if ($existing) {
                $existing->update($attrs);
                return $existing;
            }
            return CreditAccount::create($attrs + ['user_id' => $user->id, 'card_number' => $this->newCardNumber()]);
        });

        $this->audit($account, $app->user_id, $admin->id, 'application.approved', 'Approved with limit ₦' . number_format($limit), ['application_id' => $app->id, 'note' => $note]);
        // A re-approved customer may already have a working mandate.
        $this->activateIfReady($account->fresh(), false);
        $account->refresh();
        $this->notify($app->user, 'You\'re approved for Gozak Credit 🎉', $account->status === CreditAccount::ACTIVE
            ? 'Your limit is ₦' . number_format($limit) . '. Shop now and pay at the end of the month.'
            : 'Your limit is ₦' . number_format($limit) . '. Link your bank account to start shopping now and pay at the end of the month.', ['screen' => 'credit']);
        return $account;
    }

    public function reject(CreditApplication $app, User $admin, string $reason): void
    {
        if ($app->status !== 'pending') {
            throw new \RuntimeException('This application has already been decided.');
        }
        $app->update(['status' => 'rejected', 'decision_note' => $reason, 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
        $this->audit(null, $app->user_id, $admin->id, 'application.rejected', 'Application rejected', ['application_id' => $app->id, 'reason' => $reason]);
        $this->notify($app->user, 'About your Gozak Credit application', 'We can\'t offer you credit right now. You can apply again after a few more orders.', ['screen' => 'credit']);
    }

    protected function newCardNumber(): string
    {
        // Display number only (not a payment card): "6077 …" with a Luhn check digit.
        do {
            $digits = '6077' . str_pad((string) random_int(0, 99999999999), 11, '0', STR_PAD_LEFT);
            $sum = 0;
            foreach (array_reverse(str_split($digits)) as $i => $d) {
                $d = (int) $d;
                if ($i % 2 === 0) {
                    $d *= 2;
                    if ($d > 9) {
                        $d -= 9;
                    }
                }
                $sum += $d;
            }
            $number = $digits . ((10 - $sum % 10) % 10);
        } while (CreditAccount::where('card_number', $number)->exists());
        return $number;
    }

    // ── Ledger ───────────────────────────────────────────────────────────────

    /**
     * Post one ledger entry. Call inside $this->locked() so the account row
     * is locked for the whole transaction.
     */
    public function post(CreditAccount $a, string $type, string $direction, float $amount, string $description, array $extra = []): CreditLedgerEntry
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }
        $signed = $direction === 'debit' ? $amount : -$amount;
        $a->balance = round($a->balance + $signed, 2);

        if (empty($extra['statement_id']) && !in_array($type, ['payment', 'late_fee'], true)) {
            if ($direction === 'debit') {
                $a->unbilled = round($a->unbilled + $amount, 2);
            } else {
                // Refunds / reversals / credit adjustments first reduce bills
                // that are already issued (oldest first), so we never auto-debit
                // money the customer got back. The rest reduces the next bill.
                $left = $amount;
                $alloc = [];
                $open = CreditStatement::where('credit_account_id', $a->id)->whereIn('status', CreditStatement::OPEN)->orderBy('due_date')->lockForUpdate()->get();
                foreach ($open as $st) {
                    if ($left <= 0) {
                        break;
                    }
                    $take = min($left, $st->outstanding());
                    if ($take <= 0) {
                        continue;
                    }
                    $st->amount_due = round($st->amount_due - $take, 2);
                    $st->credits = round($st->credits + $take, 2);
                    if ($st->outstanding() <= 0.009) {
                        $st->status = 'paid';
                        $st->paid_at = now();
                        $st->next_attempt_at = null;
                    }
                    $st->save();
                    $alloc[$st->id] = $take;
                    $left = round($left - $take, 2);
                }
                $a->unbilled = round($a->unbilled - $left, 2);
                if ($alloc) {
                    $extra['meta'] = ($extra['meta'] ?? []) + ['statement_allocations' => $alloc, 'unbilled_part' => $left];
                    if ($left <= 0) {
                        // Fully used against an issued bill → belongs to that bill, not the next one.
                        $extra['statement_id'] = array_key_first($alloc);
                    }
                }
            }
        }
        if ($direction === 'credit') {
            $this->liftSuspensionIfClear($a);
        }
        $a->save();

        return CreditLedgerEntry::create([
            'credit_account_id' => $a->id,
            'statement_id'      => $extra['statement_id'] ?? null,
            'type'              => $type,
            'direction'         => $direction,
            'amount'            => $amount,
            'balance_after'     => $a->balance,
            'order_id'          => $extra['order_id'] ?? null,
            'reference'         => $extra['reference'] ?? null,
            'description'       => Str::limit($description, 250),
            'meta'              => $extra['meta'] ?? null,
            'created_by'        => $extra['created_by'] ?? null,
        ]);
    }

    /** Nothing overdue any more → lift an automatic suspension (caller saves). */
    public function liftSuspensionIfClear(CreditAccount $a): void
    {
        if ($a->status === CreditAccount::SUSPENDED && !CreditStatement::where('credit_account_id', $a->id)->where('status', 'overdue')
            ->whereColumn('amount_paid', '<', 'amount_due')->exists()) {
            $a->status = CreditAccount::ACTIVE;
            $a->status_reason = null;
        }
    }

    /** Run $fn with the account row locked inside a DB transaction. */
    public function locked(int $accountId, callable $fn)
    {
        return DB::transaction(function () use ($accountId, $fn) {
            $a = CreditAccount::whereKey($accountId)->lockForUpdate()->firstOrFail();
            return $fn($a);
        });
    }

    /** Rebuild the cached balance/unbilled from the ledger (admin "Recalculate"). */
    public function recalculate(CreditAccount $a): array
    {
        return $this->locked($a->id, function (CreditAccount $a) {
            $signed = "COALESCE(SUM(CASE WHEN direction='debit' THEN amount ELSE -amount END),0)";
            $before = ['balance' => $a->balance, 'unbilled' => $a->unbilled];
            $a->balance = round((float) CreditLedgerEntry::where('credit_account_id', $a->id)->selectRaw("$signed s")->value('s'), 2);
            // Everything owed that is not on an issued bill is "unbilled".
            $billed = CreditStatement::where('credit_account_id', $a->id)->whereIn('status', CreditStatement::OPEN)->get()->sum(fn ($s) => $s->outstanding());
            $a->unbilled = round($a->balance - $billed, 2);
            $a->save();
            return ['before' => $before, 'after' => ['balance' => $a->balance, 'unbilled' => $a->unbilled]];
        });
    }

    public function quote(CreditAccount $a, float $total): array
    {
        $markup = $a->markup();
        $fee = round($total * $markup / 100, 2);
        return ['markup_percent' => $markup, 'fee' => $fee, 'total' => round($total + $fee, 2)];
    }

    // ── Checkout ─────────────────────────────────────────────────────────────

    /** Pay an order with Gozak Credit. Idempotent per order. */
    public function payOrder(User $user, Order $order): array
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw new \RuntimeException('This order does not belong to you.');
        }
        if (!$this->isOfferedTo($user)) {
            throw new \RuntimeException('Gozak Credit is not available on your account.');
        }
        $account = $this->accountFor($user);
        if (!$account) {
            throw new \RuntimeException('You don\'t have a Gozak Credit account yet.');
        }

        $result = $this->locked($account->id, function (CreditAccount $a) use ($order, $user) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (CreditLedgerEntry::where('credit_account_id', $a->id)->where('order_id', $order->id)->where('type', 'purchase')->exists()) {
                return ['already' => true, 'fee' => (float) $order->credit_fee, 'total' => (float) $order->total_amount + (float) $order->credit_fee];
            }
            if (in_array($order->payment_status, ['paid', 'refunded'], true)) {
                throw new \RuntimeException('This order has already been paid.');
            }
            if ($order->status === 'cancelled') {
                throw new \RuntimeException('This order was cancelled.');
            }
            if (!$a->canSpend()) {
                throw new \RuntimeException(match ($a->status) {
                    CreditAccount::PENDING_MANDATE => 'Link your bank account to start using Gozak Credit.',
                    CreditAccount::SUSPENDED       => 'Your Gozak Credit is paused because a payment is overdue. Pay your balance to continue.',
                    CreditAccount::FROZEN          => 'Your Gozak Credit is on hold. Please contact support.',
                    default                        => 'Gozak Credit is not available on your account.',
                });
            }

            if ((float) $order->total_amount <= 0) {
                throw new \RuntimeException('This order has nothing to pay.');
            }
            $q = $this->quote($a, (float) $order->total_amount);
            if ($q['total'] > $a->available() + 0.001) {
                throw new \RuntimeException('Not enough credit. Available: ₦' . number_format($a->available(), 2) . ', needed: ₦' . number_format($q['total'], 2) . '.');
            }

            $ref = 'GZC-ORD-' . strtoupper(Str::random(10));
            $short = strtoupper(substr(str_replace('-', '', $order->id), 0, 8));
            $meta = ['markup_percent' => $q['markup_percent'], 'goods' => (float) $order->total_amount];
            $this->post($a, 'purchase', 'debit', (float) $order->total_amount, "Order #{$short}", ['order_id' => $order->id, 'reference' => $ref, 'meta' => $meta]);
            if ($q['fee'] > 0) {
                $this->post($a, 'fee', 'debit', $q['fee'], "Credit fee {$q['markup_percent']}% · Order #{$short}", ['order_id' => $order->id, 'reference' => $ref . '-F', 'meta' => $meta]);
            }

            Transaction::create([
                'user_id'        => $user->id,
                'order_id'       => $order->id,
                'reference'      => $ref,
                'amount'         => $q['total'],
                'status'         => 'success',
                'payment_method' => self::GATEWAY,
                'payment_data'   => ['credit_account_id' => $a->id, 'fee' => $q['fee'], 'markup_percent' => $q['markup_percent']],
                'paid_at'        => now(),
            ]);

            $order->forceFill([
                'payment_method' => self::GATEWAY,
                'payment_status' => 'paid',
                'paid_at'        => now(),
                'status'         => $order->status === 'pending' ? 'processing' : $order->status,
                'credit_fee'     => $q['fee'],
            ])->save();

            return ['already' => false, 'fee' => $q['fee'], 'total' => $q['total']];
        });

        if (!$result['already']) {
            $short = strtoupper(substr(str_replace('-', '', $order->id), 0, 8));
            $this->audit($account, $user->id, $user->id, 'purchase', "Paid order #{$short} — ₦" . number_format($result['total'], 2), ['order_id' => $order->id]);
            try {
                app(BarcodeService::class)->generateBarcodeForOrder($order->fresh());
                app(NotificationService::class)->sendOrderConfirmation($order->fresh('user'));
            } catch (\Throwable $e) {
                Log::info('Credit order follow-ups skipped: ' . $e->getMessage());
            }
        }
        return $result + ['account' => $account->fresh()];
    }

    /** Give credit back for a refunded (part of an) order, plus the matching share of the fee. */
    public function refundOrder(Order $order, float $amount, ?int $actorId = null, string $reason = 'Refund'): ?CreditLedgerEntry
    {
        $purchase = CreditLedgerEntry::where('order_id', $order->id)->where('type', 'purchase')->first();
        if (!$purchase) {
            return null;
        }
        $entry = $this->locked($purchase->credit_account_id, function (CreditAccount $a) use ($order, $amount, $actorId, $reason, $purchase) {
            $markup = (float) ($purchase->meta['markup_percent'] ?? 0);
            $credit = round($amount * (1 + $markup / 100), 2);
            $charged = (float) CreditLedgerEntry::where('order_id', $order->id)->whereIn('type', ['purchase', 'fee'])->sum('amount');
            $returned = (float) CreditLedgerEntry::where('order_id', $order->id)->whereIn('type', ['refund', 'reversal'])->sum('amount');
            $credit = min($credit, round($charged - $returned, 2));
            if ($credit <= 0) {
                return null;
            }
            $short = strtoupper(substr(str_replace('-', '', $order->id), 0, 8));
            return $this->post($a, 'refund', 'credit', $credit, "{$reason} · Order #{$short}", ['order_id' => $order->id, 'created_by' => $actorId, 'meta' => ['goods_amount' => $amount, 'markup_percent' => $markup]]);
        });
        if ($entry) {
            $this->audit($entry->account, $order->user_id, $actorId, 'order.refunded', '₦' . number_format($entry->amount, 2) . ' credited back for a refund', ['order_id' => $order->id]);
            $this->notify($order->user, 'Refund added to Gozak Credit', '₦' . number_format($entry->amount, 2) . ' was credited back to your Gozak Credit.', ['screen' => 'credit']);
        }
        return $entry;
    }

    /** Order cancelled → give everything back. */
    public function reverseOrder(Order $order, ?int $actorId = null): ?CreditLedgerEntry
    {
        $purchase = CreditLedgerEntry::where('order_id', $order->id)->where('type', 'purchase')->first();
        if (!$purchase) {
            return null;
        }
        $entry = $this->locked($purchase->credit_account_id, function (CreditAccount $a) use ($order, $actorId) {
            $charged = (float) CreditLedgerEntry::where('order_id', $order->id)->whereIn('type', ['purchase', 'fee'])->sum('amount');
            $returned = (float) CreditLedgerEntry::where('order_id', $order->id)->whereIn('type', ['refund', 'reversal'])->sum('amount');
            $left = round($charged - $returned, 2);
            if ($left <= 0) {
                return null;
            }
            $short = strtoupper(substr(str_replace('-', '', $order->id), 0, 8));
            return $this->post($a, 'reversal', 'credit', $left, "Order #{$short} cancelled", ['order_id' => $order->id, 'created_by' => $actorId]);
        });
        if ($entry) {
            Order::whereKey($order->id)->update(['payment_status' => 'refunded']);
            $this->audit($entry->account, $order->user_id, $actorId, 'order.reversed', 'Order cancelled — ₦' . number_format($entry->amount, 2) . ' returned to credit', ['order_id' => $order->id]);
        }
        return $entry;
    }

    // ── Payments ─────────────────────────────────────────────────────────────

    /**
     * Record money received (auto-debit, in-app payment or manual) and apply
     * it to open statements, oldest first. Idempotent on $reference.
     */
    public function recordPayment(CreditAccount $account, float $amount, string $channel, string $reference, ?int $actorId = null, ?string $note = null): ?CreditLedgerEntry
    {
        if (CreditLedgerEntry::where('reference', $reference)->exists()) {
            return null;
        }
        $entry = $this->locked($account->id, function (CreditAccount $a) use ($amount, $channel, $reference, $actorId, $note) {
            if (CreditLedgerEntry::where('reference', $reference)->exists()) {
                return null; // raced with a webhook
            }
            $left = round($amount, 2);
            $allocations = [];
            $today = now(CreditSetting::TZ)->startOfDay();
            $statements = CreditStatement::where('credit_account_id', $a->id)->whereIn('status', CreditStatement::OPEN)->orderBy('due_date')->lockForUpdate()->get();
            foreach ($statements as $st) {
                if ($left <= 0) {
                    break;
                }
                $pay = min($left, $st->outstanding());
                if ($pay <= 0) {
                    continue;
                }
                $st->amount_paid = round($st->amount_paid + $pay, 2);
                $st->payments = round($st->payments + $pay, 2);
                $late = $st->due_date->copy()->startOfDay()->lt($today);
                if ($st->outstanding() <= 0.009) {
                    $st->status = 'paid';
                    $st->paid_at = now();
                    $st->next_attempt_at = null;
                    $late ? $a->late_payments++ : $a->on_time_payments++;
                } else {
                    $st->status = $late ? 'overdue' : 'partially_paid';
                }
                $st->save();
                $allocations[$st->id] = $pay;
                $left = round($left - $pay, 2);
            }

            $labels = ['direct_debit' => 'Automatic debit (bank)', 'card' => 'Automatic debit (card)', 'checkout' => 'Payment in app', 'manual' => 'Payment recorded by Gozak'];
            $entry = $this->post($a, 'payment', 'credit', $amount, ($labels[$channel] ?? 'Payment') . ($note ? ' — ' . $note : ''), [
                'reference'  => $reference,
                'created_by' => $actorId,
                'meta'       => ['channel' => $channel, 'allocations' => $allocations, 'unallocated' => $left],
            ]);
            // Money beyond the issued bills pays down this month's spending early.
            if ($left > 0) {
                $a->unbilled = round($a->unbilled - $left, 2);
            }

            $this->liftSuspensionIfClear($a);
            $a->save();
            return $entry;
        });

        if ($entry) {
            $account->refresh();
            $this->audit($account, $account->user_id, $actorId, 'payment.received', '₦' . number_format($amount, 2) . ' received (' . $channel . ')', ['reference' => $reference]);
            $this->notify($account->user, 'Payment received ✅', '₦' . number_format($amount, 2) . ' was applied to your Gozak Credit. Available now: ₦' . number_format($account->available(), 2) . '.', ['screen' => 'credit']);
        }
        return $entry;
    }

    // ── Admin actions ────────────────────────────────────────────────────────

    public function adjust(CreditAccount $account, float $amount, string $reason, User $admin): CreditLedgerEntry
    {
        $entry = $this->locked($account->id, fn (CreditAccount $a) => $this->post($a, 'adjustment', $amount >= 0 ? 'debit' : 'credit', abs($amount), 'Adjustment: ' . $reason, ['created_by' => $admin->id]));
        $this->audit($account, $account->user_id, $admin->id, 'adjustment', ($amount >= 0 ? 'Charged ' : 'Credited ') . '₦' . number_format(abs($amount), 2) . ' — ' . $reason);
        return $entry;
    }

    public function waiveLateFee(CreditStatement $st, User $admin): void
    {
        $fee = CreditLedgerEntry::where('statement_id', $st->id)->where('type', 'late_fee')->first();
        if (!$fee) {
            throw new \RuntimeException('This statement has no late fee.');
        }
        if (CreditLedgerEntry::where('reference', 'WAIVE-' . $fee->id)->exists()) {
            throw new \RuntimeException('This late fee was already waived.');
        }
        $this->locked($st->credit_account_id, function (CreditAccount $a) use ($st, $fee, $admin) {
            $this->post($a, 'adjustment', 'credit', $fee->amount, 'Late fee waived · ' . $st->number, ['statement_id' => $st->id, 'reference' => 'WAIVE-' . $fee->id, 'created_by' => $admin->id]);
            $st = CreditStatement::whereKey($st->id)->lockForUpdate()->first();
            // Take the fee off what is still owed on this bill; anything already
            // paid towards it comes off the next bill instead.
            $fromBill = min($fee->amount, $st->outstanding());
            $st->amount_due = round($st->amount_due - $fromBill, 2);
            $st->fees = round(max(0, $st->fees - $fee->amount), 2);
            $a->unbilled = round($a->unbilled - ($fee->amount - $fromBill), 2);
            $a->save();
            if ($st->outstanding() <= 0.009) {
                $st->status = 'paid';
                $st->paid_at = now();
                $st->next_attempt_at = null;
            }
            $st->save();
            $this->liftSuspensionIfClear($a);
            $a->save();
        });
        $this->audit($st->account, $st->account->user_id, $admin->id, 'late_fee.waived', 'Waived ₦' . number_format($fee->amount, 2) . ' late fee on ' . $st->number);
    }

    public function setLimit(CreditAccount $a, float $limit, User $admin, ?string $reason = null): void
    {
        $old = $a->credit_limit;
        $a->update(['credit_limit' => round($limit, 2)]);
        $this->audit($a, $a->user_id, $admin->id, 'limit.changed', 'Limit ₦' . number_format($old) . ' → ₦' . number_format($limit), ['reason' => $reason]);
        if ($limit > $old) {
            $this->notify($a->user, 'Your credit limit went up 🎉', 'Your Gozak Credit limit is now ₦' . number_format($limit) . '.', ['screen' => 'credit']);
        }
    }

    public function setMarkup(CreditAccount $a, ?float $percent, User $admin): void
    {
        $a->update(['markup_percent' => $percent]);
        $this->audit($a, $a->user_id, $admin->id, 'markup.changed', $percent === null ? 'Uses the standard credit fee' : 'Credit fee set to ' . $percent . '%');
    }

    public function setStatus(CreditAccount $a, string $status, User $admin, ?string $reason = null): void
    {
        if ($status === CreditAccount::CLOSED && $a->balance > 0.009) {
            throw new \RuntimeException('Collect the outstanding balance (₦' . number_format($a->balance, 2) . ') before closing this account.');
        }
        if ($status === CreditAccount::ACTIVE && !$a->activeMandates()->exists()) {
            $status = CreditAccount::PENDING_MANDATE;
        }
        $old = $a->status;
        $a->update(['status' => $status, 'status_reason' => $reason, 'closed_at' => $status === CreditAccount::CLOSED ? now() : null]);
        $this->audit($a, $a->user_id, $admin->id, 'status.changed', $old . ' → ' . $status . ($reason ? ' (' . $reason . ')' : ''));
        if ($status === CreditAccount::FROZEN) {
            $this->notify($a->user, 'Gozak Credit on hold', 'Your Gozak Credit has been put on hold. Contact support if you have questions.', ['screen' => 'credit']);
        }
    }

    /** Activate the account once a usable mandate exists. */
    public function activateIfReady(CreditAccount $a, bool $notify = true): void
    {
        if ($a->status !== CreditAccount::PENDING_MANDATE) {
            return;
        }
        $hasBank = $a->activeMandates()->where('type', 'direct_debit')->exists();
        $hasAny = $a->activeMandates()->exists();
        if ($hasBank || ($hasAny && !$this->settings()->require_mandate)) {
            $a->update(['status' => CreditAccount::ACTIVE, 'activated_at' => $a->activated_at ?? now(), 'status_reason' => null]);
            $this->audit($a, $a->user_id, null, 'account.activated', 'Account activated');
            if ($notify) {
                $this->notify($a->user, 'Gozak Credit is ready 💳', 'Pay with Gozak Credit at checkout. Available: ₦' . number_format($a->available()) . '.', ['screen' => 'credit']);
            }
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function audit(?CreditAccount $a, ?int $userId, ?int $actorId, string $action, string $summary, array $data = []): void
    {
        try {
            CreditAuditLog::create([
                'credit_account_id' => $a?->id,
                'user_id'           => $userId ?? $a?->user_id,
                'actor_id'          => $actorId,
                'action'            => $action,
                'summary'           => Str::limit($summary, 250),
                'data'              => $data ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Credit audit failed: ' . $e->getMessage());
        }
    }

    public function notify(?User $user, string $title, string $body, array $data = []): void
    {
        if (!$user) {
            return;
        }
        try {
            app(FcmService::class)->sendToUser($user, $title, $body, array_map('strval', $data + ['type' => 'credit']), 'credit');
        } catch (\Throwable $e) {
            Log::info('Credit push skipped: ' . $e->getMessage());
        }
    }

    /** Everything the app needs for the credit screen. */
    public function summary(User $user): array
    {
        $s = $this->settings();
        $a = $this->accountFor($user);
        $application = CreditApplication::where('user_id', $user->id)->latest('id')->first();

        $data = [
            'offered'        => $this->isOfferedTo($user),
            'markup_percent' => $a ? $a->markup() : $s->markup_percent,
            'statement_day'  => $s->statement_day,
            'due_day'        => $s->due_day,
            'grace_days'     => $s->grace_days,
            'late_fee'       => ['flat' => $s->late_fee_flat, 'percent' => $s->late_fee_percent, 'cap' => $s->late_fee_cap],
            'min_limit'      => $s->min_limit,
            'max_limit'      => $s->max_limit,
            'terms_version'  => $s->terms_version,
            'terms_text'     => $s->terms_text,
            'application'    => $application ? [
                'id'              => $application->id,
                'status'          => $application->status,
                'requested_limit' => $application->requested_limit,
                'created_at'      => $application->created_at?->toIso8601String(),
                'reviewed_at'     => $application->reviewed_at?->toIso8601String(),
            ] : null,
            'account'        => null,
        ];

        if ($a) {
            $open = CreditStatement::where('credit_account_id', $a->id)->whereIn('status', CreditStatement::OPEN)->orderBy('due_date')->get();
            $next = $open->first();
            $data['account'] = [
                'id'                => $a->id,
                'status'            => $a->status,
                'status_label'      => $a->statusLabel(),
                'status_reason'     => $a->status_reason,
                'credit_limit'      => $a->credit_limit,
                'balance'           => $a->balance,
                'available'         => $a->available(),
                'unbilled'          => max(0, $a->unbilled),
                'amount_due'        => round($open->sum(fn ($st) => $st->outstanding()), 2),
                'next_due_date'     => $next?->due_date?->toDateString(),
                'overdue'           => $open->contains(fn ($st) => $st->status === 'overdue'),
                'card_number'       => $a->card_number,
                'card_masked'       => $a->maskedCard(),
                'card_name'         => $a->card_name,
                'card_expires'      => $a->card_expires_on?->format('m/y'),
                'next_statement_on' => $s->statementDateFor(now())->toDateString(),
                'mandates'          => $a->mandates()->whereIn('status', ['active', 'pending'])->latest('id')->get()->map->toApi()->values(),
                'can_spend'         => $a->canSpend(),
                'on_time_payments'  => $a->on_time_payments,
                'late_payments'     => $a->late_payments,
            ];
        }
        return $data;
    }
}
