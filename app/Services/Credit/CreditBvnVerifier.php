<?php

namespace App\Services\Credit;

use App\Models\Credit\CreditApplication;
use App\Services\PaystackService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * BVN check through Paystack Customer Validation.
 *
 * Paystack (with the bank) confirms that the BVN, the bank account and the
 * first/last name all belong to the same person. The answer arrives by
 * webhook (customeridentification.success / .failed); a sweep also polls
 * GET /customer/{code} in case a webhook is missed.
 */
class CreditBvnVerifier
{
    public function __construct(protected CreditService $credit)
    {
    }

    protected function paystack(): PaystackService
    {
        return app(PaystackService::class);
    }

    /** Split "Ada Chioma Obi" → ["Ada", "Obi"] (first and last word). */
    public static function names(string $full): array
    {
        $parts = preg_split('/\s+/', trim($full)) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';
        return [$first, $last];
    }

    /** Start (or restart) the check for an application. Never throws. */
    public function start(CreditApplication $app): CreditApplication
    {
        $bvn = (string) $app->bvn;
        $account = (string) $app->account_number;
        if (strlen($bvn) !== 11 || strlen($account) !== 10 || !$app->bank_code) {
            $app->update(['bvn_status' => 'failed', 'bvn_failure_reason' => 'BVN or bank account details are missing', 'bvn_checked_at' => now()]);
            return $app;
        }

        [$first, $last] = self::names($app->full_name);
        $user = $app->user;

        try {
            // 1. Bank's name on the account (instant, also shown to the reviewer).
            try {
                $resolved = $this->paystack()->resolveAccount($account, $app->bank_code);
                $app->account_name = Str::limit($resolved['account_name'] ?? '', 150, '');
            } catch (\Throwable $e) {
                $app->update(['bvn_status' => 'failed', 'bvn_failure_reason' => 'Bank account could not be found', 'bvn_checked_at' => now()]);
                $this->credit->audit(null, $app->user_id, null, 'bvn.failed', 'Bank account could not be resolved', ['application_id' => $app->id]);
                return $app;
            }

            // 2. Paystack customer (one per user).
            $code = $user->paystack_customer_code;
            if (!$code) {
                $customer = $this->paystack()->createCustomer($user->email, $first, $last, $app->phone);
                $code = $customer['customer_code'] ?? null;
                if ($code) {
                    $user->forceFill(['paystack_customer_code' => $code])->saveQuietly();
                }
            }
            if (!$code) {
                throw new \RuntimeException('Paystack did not return a customer code');
            }

            // 3. Ask the bank to match BVN + account + names.
            $this->paystack()->validateCustomer($code, $bvn, $account, $app->bank_code, $first, $last);
            $app->fill([
                'paystack_customer_code' => $code,
                'bvn_status'             => 'pending',
                'bvn_failure_reason'     => null,
                'bvn_checked_at'         => now(),
            ])->save();
            $this->credit->audit(null, $app->user_id, null, 'bvn.started', 'BVN check sent to Paystack', ['application_id' => $app->id]);
        } catch (\Throwable $e) {
            Log::warning('Credit BVN check could not start: ' . $e->getMessage(), ['application' => $app->id]);
            $app->fill([
                'bvn_status'         => 'unverified',
                'bvn_failure_reason' => Str::limit('Could not start the check: ' . $e->getMessage(), 250),
                'bvn_checked_at'     => now(),
            ])->save();
        }
        return $app->fresh();
    }

    /** Webhook: customeridentification.success / customeridentification.failed */
    public function handleWebhook(string $event, array $data): bool
    {
        if (!str_starts_with($event, 'customeridentification.')) {
            return false;
        }
        $code = $data['customer_code'] ?? ($data['customer']['customer_code'] ?? null);
        if (!$code) {
            return true;
        }
        $app = CreditApplication::where('paystack_customer_code', $code)->whereIn('bvn_status', ['pending', 'unverified'])->latest('id')->first()
            ?? CreditApplication::where('paystack_customer_code', $code)->latest('id')->first();
        if (!$app) {
            Log::info('Credit BVN webhook: no application for ' . $code);
            return true;
        }
        if ($event === 'customeridentification.success') {
            $this->markVerified($app);
        } else {
            $this->markFailed($app, (string) ($data['reason'] ?? 'Details did not match the bank\'s records'));
        }
        return true;
    }

    public function markVerified(CreditApplication $app): void
    {
        if ($app->bvn_status === 'verified') {
            return;
        }
        $factors = $app->risk_factors ?? [];
        $factors[] = ['points' => 10, 'label' => 'BVN matched bank account (Paystack)'];
        $app->update([
            'bvn_status'         => 'verified',
            'bvn_failure_reason' => null,
            'bvn_checked_at'     => now(),
            'risk_score'         => min(100, $app->risk_score + 10),
            'risk_factors'       => $factors,
        ]);
        $this->credit->audit(null, $app->user_id, null, 'bvn.verified', 'BVN verified by Paystack', ['application_id' => $app->id]);
    }

    public function markFailed(CreditApplication $app, string $reason): void
    {
        $factors = collect($app->risk_factors ?? [])->reject(fn ($f) => str_starts_with((string) ($f['label'] ?? ''), 'BVN matched'))->values()->all();
        $wasVerified = $app->bvn_status === 'verified';
        $factors[] = ['points' => -25, 'label' => 'BVN did not match: ' . Str::limit($reason, 80)];
        $app->update([
            'bvn_status'         => 'failed',
            'bvn_failure_reason' => Str::limit($reason, 250),
            'bvn_checked_at'     => now(),
            'risk_score'         => max(0, $app->risk_score - 25 - ($wasVerified ? 10 : 0)),
            'risk_factors'       => $factors,
        ]);
        $this->credit->audit(null, $app->user_id, null, 'bvn.failed', 'BVN check failed: ' . $reason, ['application_id' => $app->id]);
    }

    /** Poll Paystack for checks still pending (missed webhooks). */
    public function sweep(): int
    {
        $n = 0;
        CreditApplication::where('bvn_status', 'pending')->whereNotNull('paystack_customer_code')
            ->where('bvn_checked_at', '<', now()->subMinutes(10))->where('bvn_checked_at', '>', now()->subDays(3))
            ->limit(100)->get()->each(function (CreditApplication $app) use (&$n) {
                try {
                    $c = $this->paystack()->fetchCustomer($app->paystack_customer_code);
                } catch (\Throwable $e) {
                    return;
                }
                if (!empty($c['identified'])) {
                    $this->markVerified($app);
                    $n++;
                }
            });
        // Still nothing after 2 days → let the reviewer know.
        CreditApplication::where('bvn_status', 'pending')->where('bvn_checked_at', '<', now()->subDays(2))
            ->update(['bvn_status' => 'unverified', 'bvn_failure_reason' => 'No answer from Paystack — run the check again']);
        return $n;
    }
}
