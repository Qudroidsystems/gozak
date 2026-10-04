<?php

namespace App\Services\Credit;

use App\Models\Credit\CreditAccount;
use App\Models\Credit\CreditCollectionAttempt;
use App\Models\Credit\CreditMandate;
use App\Models\Credit\CreditSetting;
use App\Models\Credit\CreditStatement;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Paystack side of Gozak Credit:
 *  - bank direct-debit mandates (primary way we collect)
 *  - reusable debit cards (backup)
 *  - automatic debits + in-app "Pay now"
 *  - webhooks for all of the above
 *
 * References: GZC-CARD-… (card link, ₦50 refunded), GZC-COL-… (automatic /
 * admin debit), GZC-PAY-… (customer pays in the app). Mandates use the
 * reference Paystack returns.
 */
class CreditGateway
{
    public const CARD_LINK_AMOUNT = 50;

    public function __construct(protected CreditService $credit)
    {
    }

    protected function paystack(): PaystackService
    {
        return app(PaystackService::class);
    }

    public function callbackUrl(string $kind): string
    {
        return url('/payment-callback') . '?credit=' . $kind;
    }

    // ── Bank mandate ─────────────────────────────────────────────────────────

    public function startDirectDebit(CreditAccount $a, ?string $accountNumber = null, ?string $bankCode = null): array
    {
        $user = $a->user;
        $address = [];
        $app = $a->application;
        if ($app && $app->address) {
            $address = array_filter(['street' => $app->address, 'state' => $app->state, 'city' => $app->state]);
        }
        $data = $this->paystack()->initializeDirectDebit($user->email, $this->callbackUrl('mandate'), $accountNumber, $bankCode, $address);

        $mandate = CreditMandate::create([
            'credit_account_id' => $a->id,
            'user_id'           => $user->id,
            'type'              => 'direct_debit',
            'status'            => 'pending',
            'reference'         => $data['reference'] ?? ('GZC-DD-' . Str::upper(Str::random(10))),
            'email'             => $user->email,
            'gateway_data'      => ['init' => $data],
        ]);
        $this->credit->audit($a, $a->user_id, $a->user_id, 'mandate.started', 'Started bank direct-debit setup');

        return ['mandate_id' => $mandate->id, 'reference' => $mandate->reference, 'url' => $data['redirect_url'] ?? null, 'callback_url' => $this->callbackUrl('mandate')];
    }

    /** Ask Paystack whether a pending mandate is active yet. */
    public function refreshMandate(CreditMandate $m): CreditMandate
    {
        if ($m->type !== 'direct_debit' || !$m->reference || $m->status === 'active') {
            return $m;
        }
        try {
            $data = $this->paystack()->verifyAuthorization($m->reference);
        } catch (\Throwable $e) {
            Log::info('Credit mandate verify failed: ' . $e->getMessage());
            return $m;
        }
        return $this->applyMandateData($m, $data);
    }

    protected function applyMandateData(CreditMandate $m, array $data): CreditMandate
    {
        $active = !empty($data['active']) || ($data['status'] ?? null) === 'active';
        $code = $data['authorization_code'] ?? null;
        $m->fill([
            'bank_name'    => $data['bank'] ?? $m->bank_name,
            'account_name' => $data['account_name'] ?? $m->account_name,
            'last4'        => $data['last4'] ?? $m->last4,
            'gateway_data' => array_merge($m->gateway_data ?? [], ['verify' => $data]),
        ]);
        if ($code) {
            $m->authorization_code = $code;
        }
        if ($active && ($code || $m->authorization_code)) {
            $this->activateMandate($m);
        } else {
            $m->save();
        }
        return $m->fresh();
    }

    protected function activateMandate(CreditMandate $m): void
    {
        DB::transaction(function () use ($m) {
            $hasPrimary = CreditMandate::where('credit_account_id', $m->credit_account_id)->where('status', 'active')->where('is_primary', true)->where('id', '!=', $m->id)->exists();
            $m->status = 'active';
            $m->activated_at = $m->activated_at ?? now();
            // A bank mandate always becomes primary; a card only if nothing else is.
            if (!$hasPrimary || $m->type === 'direct_debit') {
                CreditMandate::where('credit_account_id', $m->credit_account_id)->where('id', '!=', $m->id)->update(['is_primary' => false]);
                $m->is_primary = true;
            }
            $m->save();
        });
        $a = $m->account;
        $this->credit->audit($a, $a->user_id, null, 'mandate.active', $m->label() . ' linked for automatic payments');
        $this->credit->activateIfReady($a->fresh());
    }

    public function revokeMandate(CreditMandate $m, ?User $by = null): void
    {
        if ($m->authorization_code) {
            try {
                $this->paystack()->deactivateAuthorization($m->authorization_code);
            } catch (\Throwable $e) {
                Log::info('Credit: deactivate authorization failed: ' . $e->getMessage());
            }
        }
        $m->update(['status' => 'revoked', 'revoked_at' => now(), 'is_primary' => false]);
        $a = $m->account;
        if ($next = $a->activeMandates()->orderByRaw("type = 'direct_debit' desc")->first()) {
            $next->update(['is_primary' => true]);
        }
        $this->credit->audit($a, $a->user_id, $by?->id, 'mandate.revoked', $m->label() . ' removed');
        if ($a->status === CreditAccount::ACTIVE && $this->credit->settings()->require_mandate && !$a->activeMandates()->where('type', 'direct_debit')->exists()) {
            $a->update(['status' => CreditAccount::PENDING_MANDATE, 'status_reason' => 'No bank account linked']);
        }
    }

    public function makePrimary(CreditMandate $m): void
    {
        if ($m->status !== 'active') {
            throw new \RuntimeException('Only an active payment method can be the default.');
        }
        DB::transaction(function () use ($m) {
            CreditMandate::where('credit_account_id', $m->credit_account_id)->update(['is_primary' => false]);
            $m->update(['is_primary' => true]);
        });
    }

    // ── Card backup ──────────────────────────────────────────────────────────

    public function startCardLink(CreditAccount $a): array
    {
        $ref = 'GZC-CARD-' . Str::upper(Str::random(12));
        $user = $a->user;
        $data = $this->paystack()->initializeCheckout($user->email, self::CARD_LINK_AMOUNT, $ref, $this->callbackUrl('card'), [
            'purpose'           => 'credit_card_link',
            'credit_account_id' => $a->id,
            'custom_fields'     => [['display_name' => 'Purpose', 'variable_name' => 'purpose', 'value' => 'Link card to Gozak Credit (₦50 refunded)']],
        ], ['card']);

        CreditMandate::create([
            'credit_account_id' => $a->id,
            'user_id'           => $user->id,
            'type'              => 'card',
            'status'            => 'pending',
            'reference'         => $ref,
            'email'             => $user->email,
        ]);
        return ['reference' => $ref, 'url' => $data['authorization_url'] ?? null, 'callback_url' => $this->callbackUrl('card')];
    }

    public function confirmCard(string $reference, ?array $data = null): ?CreditMandate
    {
        $m = CreditMandate::where('reference', $reference)->where('type', 'card')->first();
        if (!$m || $m->status === 'active') {
            return $m;
        }
        try {
            $data = $data ?? $this->paystack()->verifyTransactionData($reference);
        } catch (\Throwable $e) {
            return $m;
        }
        if (($data['status'] ?? null) !== 'success') {
            if (in_array($data['status'] ?? '', ['failed', 'abandoned'], true)) {
                $m->update(['status' => 'failed']);
            }
            return $m;
        }
        $auth = $data['authorization'] ?? [];
        if (empty($auth['reusable']) || empty($auth['authorization_code'])) {
            $m->update(['status' => 'failed', 'gateway_data' => ['note' => 'This card cannot be charged automatically']]);
            $this->refundCardLink($reference);
            return $m;
        }
        if (!empty($auth['signature']) && CreditMandate::where('credit_account_id', $m->credit_account_id)->where('id', '!=', $m->id)->where('signature', $auth['signature'])->where('status', 'active')->exists()) {
            $m->update(['status' => 'revoked', 'revoked_at' => now(), 'gateway_data' => ['note' => 'Card already linked']]);
            $this->refundCardLink($reference);
            return $m;
        }
        $m->fill([
            'authorization_code' => $auth['authorization_code'],
            'email'              => $data['customer']['email'] ?? $m->email,
            'card_brand'         => trim((string) ($auth['card_type'] ?? $auth['brand'] ?? 'card')),
            'bank_name'          => $auth['bank'] ?? null,
            'last4'              => $auth['last4'] ?? null,
            'expiry'             => isset($auth['exp_month'], $auth['exp_year']) ? sprintf('%02d/%s', $auth['exp_month'], $auth['exp_year']) : null,
            'signature'          => $auth['signature'] ?? null,
            'gateway_data'       => ['channel' => $auth['channel'] ?? 'card'],
        ]);
        $this->activateMandate($m);
        $this->refundCardLink($reference);
        return $m->fresh();
    }

    protected function refundCardLink(string $reference): void
    {
        try {
            $this->paystack()->createRefund($reference, null, 'Gozak Credit card verification', 'Your ₦50 card check is being refunded');
        } catch (\Throwable $e) {
            Log::info('Credit: card-link refund failed for ' . $reference . ': ' . $e->getMessage());
        }
    }

    // ── Collections ──────────────────────────────────────────────────────────

    /**
     * Debit a statement through a saved mandate.
     * $prefer: 'direct_debit' | 'card' | null (the default method).
     */
    public function collect(CreditStatement $st, string $trigger = 'auto', ?User $by = null, ?string $prefer = null, ?float $amount = null): ?CreditCollectionAttempt
    {
        $a = $st->account;
        $mandates = $a->activeMandates()->get();
        $mandate = $prefer ? $mandates->where('type', $prefer)->sortByDesc('is_primary')->first() : null;
        $mandate = $mandate ?? $mandates->firstWhere('is_primary', true) ?? $mandates->first();
        if (!$mandate) {
            throw new \RuntimeException('No active bank mandate or card to debit.');
        }

        // Check-and-create under a row lock so the cron and an admin can't start two debits.
        $attempt = DB::transaction(function () use ($st, $a, $mandate, $trigger, $by, $amount) {
            $st = CreditStatement::whereKey($st->id)->lockForUpdate()->firstOrFail();
            if (CreditCollectionAttempt::where('statement_id', $st->id)->whereIn('status', ['pending', 'processing'])
                ->whereIn('channel', ['direct_debit', 'card'])->exists()) {
                throw new \RuntimeException('A debit for this statement is already in progress.');
            }
            $amount = round($amount ?? $st->outstanding(), 2);
            if ($amount <= 0) {
                return null;
            }
            $attempt = CreditCollectionAttempt::create([
                'credit_account_id' => $a->id,
                'statement_id'      => $st->id,
                'mandate_id'        => $mandate->id,
                'channel'           => $mandate->type,
                'trigger'           => $trigger,
                'amount'            => $amount,
                'reference'         => 'GZC-COL-' . $st->id . '-' . Str::upper(Str::random(8)),
                'status'            => 'processing',
                'initiated_by'      => $by?->id,
            ]);
            $st->attempts = $st->attempts + 1;
            $st->next_attempt_at = null;
            $st->save();
            return $attempt;
        });
        if (!$attempt) {
            return null;
        }
        $amount = $attempt->amount;

        try {
            $data = $this->paystack()->chargeAuthorization($mandate->authorization_code, $mandate->email, $amount, $attempt->reference, [
                'purpose'           => 'credit_collection',
                'credit_account_id' => $a->id,
                'statement_id'      => $st->id,
            ]);
            $attempt->update(['gateway_response' => $data]);
            $status = $data['status'] ?? 'processing';
            if ($status === 'success') {
                $this->settleAttempt($attempt->fresh(), $data);
            } elseif (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
                $this->failAttempt($attempt->fresh(), $data['gateway_response'] ?? 'Declined');
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // No answer — the charge may still have gone through. Leave it
            // "processing"; sweepProcessing() verifies it with Paystack.
            $attempt->update(['failure_reason' => 'No response from Paystack yet; verifying']);
        } catch (\Throwable $e) {
            $this->failAttempt($attempt->fresh(), $e->getMessage());
        }
        $this->credit->audit($a, $a->user_id, $by?->id, 'collection.attempted', '₦' . number_format($amount, 2) . ' debit via ' . $mandate->label() . ' (' . $trigger . ')', ['attempt_id' => $attempt->id]);
        return $attempt->fresh();
    }

    public function settleAttempt(CreditCollectionAttempt $attempt, array $data = []): void
    {
        $attempt = CreditCollectionAttempt::whereKey($attempt->id)->first();
        if (!$attempt || $attempt->status === 'success') {
            return;
        }
        $paid = isset($data['amount']) ? round(((float) $data['amount']) / 100, 2) : $attempt->amount;
        $attempt->update(['status' => 'success', 'completed_at' => now(), 'gateway_response' => $data ?: $attempt->gateway_response, 'failure_reason' => null]);
        $attempt->mandate?->update(['failures' => 0]);
        $this->credit->recordPayment($attempt->account, $paid, $attempt->channel, $attempt->reference, $attempt->initiated_by);
    }

    public function failAttempt(CreditCollectionAttempt $attempt, ?string $reason): void
    {
        if (in_array($attempt->status, ['success', 'failed'], true)) {
            return;
        }
        $attempt->update(['status' => 'failed', 'completed_at' => now(), 'failure_reason' => Str::limit((string) $reason, 250)]);
        $attempt->mandate?->increment('failures');
        $st = $attempt->statement;
        if ($st && $st->isOpen() && in_array($attempt->channel, ['direct_debit', 'card'], true)) {
            $hours = max(1, (int) CreditSetting::current()->retry_hours);
            $st->update(['next_attempt_at' => now()->addHours($hours)]);
        }
    }

    /** Re-check debits still "processing" and pending mandates (missed webhooks, slow banks). */
    public function sweepProcessing(): int
    {
        $n = 0;
        CreditCollectionAttempt::whereIn('status', ['pending', 'processing'])->where('created_at', '<', now()->subMinutes(20))->limit(200)->get()
            ->each(function (CreditCollectionAttempt $at) use (&$n) {
                try {
                    $data = $this->paystack()->verifyTransactionData($at->reference);
                } catch (\Throwable $e) {
                    // Abandoned in-app checkouts never reach Paystack; drop them after a day.
                    if ($at->created_at->lt(now()->subDays($at->channel === 'checkout' ? 1 : 3))) {
                        $this->failAttempt($at, $at->channel === 'checkout' ? 'Not completed' : 'No result from Paystack after 3 days');
                    }
                    return;
                }
                $status = $data['status'] ?? '';
                if ($status === 'success') {
                    $this->settleAttempt($at, $data);
                    $n++;
                } elseif (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
                    $this->failAttempt($at, $data['gateway_response'] ?? $status);
                    $n++;
                }
            });
        CreditMandate::where('type', 'direct_debit')->where('status', 'pending')->where('created_at', '>', now()->subDays(3))->limit(100)->get()
            ->each(fn ($m) => $this->refreshMandate($m));
        CreditMandate::where('type', 'card')->where('status', 'pending')->whereBetween('created_at', [now()->subDay(), now()->subMinutes(10)])->limit(100)->get()
            ->each(fn ($m) => $this->confirmCard($m->reference));
        return $n;
    }

    // ── Customer pays in the app ─────────────────────────────────────────────

    public function startRepayment(CreditAccount $a, float $amount): array
    {
        $amount = round($amount, 2);
        if ($amount < 100) {
            throw new \InvalidArgumentException('The minimum payment is ₦100.');
        }
        if ($amount > $a->balance + 0.009) {
            throw new \InvalidArgumentException('You only owe ₦' . number_format($a->balance, 2) . '.');
        }
        $st = CreditStatement::where('credit_account_id', $a->id)->whereIn('status', CreditStatement::OPEN)->orderBy('due_date')->first();
        $ref = 'GZC-PAY-' . Str::upper(Str::random(12));
        $data = $this->paystack()->initializeCheckout($a->user->email, $amount, $ref, $this->callbackUrl('pay'), [
            'purpose'           => 'credit_repayment',
            'credit_account_id' => $a->id,
        ]);
        CreditCollectionAttempt::create([
            'credit_account_id' => $a->id,
            'statement_id'      => $st?->id,
            'channel'           => 'checkout',
            'trigger'           => 'customer',
            'amount'            => $amount,
            'reference'         => $ref,
            'status'            => 'pending',
            'initiated_by'      => $a->user_id,
        ]);
        return ['reference' => $ref, 'url' => $data['authorization_url'] ?? null, 'callback_url' => $this->callbackUrl('pay')];
    }

    public function verifyRepayment(string $reference): ?CreditCollectionAttempt
    {
        $at = CreditCollectionAttempt::where('reference', $reference)->first();
        if (!$at || $at->status === 'success') {
            return $at;
        }
        try {
            $data = $this->paystack()->verifyTransactionData($reference);
        } catch (\Throwable $e) {
            return $at;
        }
        if (($data['status'] ?? '') === 'success') {
            $this->settleAttempt($at, $data);
        } elseif (in_array($data['status'] ?? '', ['failed', 'abandoned'], true)) {
            $this->failAttempt($at, $data['gateway_response'] ?? $data['status']);
        }
        return $at->fresh();
    }

    // ── Webhooks ─────────────────────────────────────────────────────────────

    /** Returns true when the event belonged to Gozak Credit (handled here). */
    public function handleWebhook(array $event): bool
    {
        $type = (string) ($event['event'] ?? '');
        $data = $event['data'] ?? [];

        if (str_starts_with($type, 'direct_debit.')) {
            $m = null;
            if (!empty($data['reference'])) {
                $m = CreditMandate::where('reference', $data['reference'])->first();
            }
            if (!$m && !empty($data['authorization_code'])) {
                $m = CreditMandate::where('type', 'direct_debit')->get()->first(fn ($x) => $x->authorization_code === $data['authorization_code']);
            }
            if (!$m && !empty($data['customer']['email'])) {
                $m = CreditMandate::where('type', 'direct_debit')->where('status', 'pending')->where('email', $data['customer']['email'])->latest('id')->first();
            }
            if (!$m) {
                Log::warning('Credit webhook: mandate not found', ['event' => $type]);
                return true;
            }
            $this->applyMandateData($m, $type === 'direct_debit.authorization.active' ? $data + ['active' => true] : $data);
            return true;
        }

        $ref = (string) ($data['reference'] ?? '');
        if (!str_starts_with($ref, 'GZC-')) {
            return false;
        }

        if (str_starts_with($ref, 'GZC-CARD-')) {
            if ($type === 'charge.success') {
                $this->confirmCard($ref, $data);
            } elseif ($type === 'charge.failed') {
                CreditMandate::where('reference', $ref)->where('status', 'pending')->update(['status' => 'failed']);
            }
            return true;
        }

        $at = CreditCollectionAttempt::where('reference', $ref)->first();
        if (!$at) {
            Log::warning('Credit webhook: attempt not found', ['reference' => $ref]);
            return true;
        }
        if ($type === 'charge.success') {
            $this->settleAttempt($at, $data);
        } elseif ($type === 'charge.failed') {
            $this->failAttempt($at, $data['gateway_response'] ?? 'Declined');
        }
        return true;
    }
}
