<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OPay Checkout (cashier) — Nigeria (country NG, NGN). Ported from CSS Kabba.
 *
 * Keys come from Settings › Payment Gateways (provider_key "opay"):
 *   merchant_id, public_key (creates the cashier) and secret_key (the
 *   "private key", signs status queries). Sandbox keys hit
 *   testapi.opaycheckout.com, live keys liveapi.opaycheckout.com.
 *
 * initialize()/verify() return the same shape as the Paystack API
 * (['status' => true, 'data' => [...]]) so PaymentController can treat
 * both gateways the same way. Amounts are in kobo both ways.
 */
class OpayGateway
{
    protected const LIVE = 'https://liveapi.opaycheckout.com';
    protected const TEST = 'https://testapi.opaycheckout.com';

    protected ?string $merchantId = null;
    protected ?string $publicKey = null;
    protected ?string $secretKey = null;
    protected string $mode = 'sandbox';
    protected bool $active = false;
    protected bool $hasRow = false;

    public function __construct()
    {
        $gw = PaymentGateway::where('provider_key', 'opay')->first();
        $this->hasRow = (bool) $gw;
        if (!$gw) return;
        $this->mode       = $gw->mode === 'live' ? 'live' : 'sandbox';
        $set              = $this->mode === 'live' ? 'live' : 'test';
        $this->merchantId = $gw->credential('merchant_id', $set);
        $this->publicKey  = $gw->credential('public_key', $set);
        $this->secretKey  = $gw->credential('secret_key', $set);
        $this->active     = (bool) $gw->is_active;
    }

    public function mode(): string { return $this->mode; }

    public function problem(): ?string
    {
        if (!$this->hasRow)  return 'OPay has not been set up.';
        if (!$this->active)  return 'OPay is switched off.';
        if (!$this->merchantId || !$this->publicKey || !$this->secretKey) {
            return 'OPay ' . ($this->mode === 'live' ? 'live' : 'test') . ' keys are incomplete.';
        }
        return null;
    }

    public function isReady(): bool
    {
        return $this->problem() === null;
    }

    protected function base(): string
    {
        return $this->mode === 'live' ? self::LIVE : self::TEST;
    }

    /** HMAC-SHA512 of the key-sorted JSON body, signed with the private key. */
    public static function sign(string $json, string $secret): string
    {
        return hash_hmac('sha512', $json, $secret);
    }

    protected static function body(array $data): string
    {
        ksort($data);
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function generateReference(): string
    {
        return 'OPAY_' . time() . '_' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Open an OPay cashier. Returns Paystack-shaped data:
     * ['status' => true, 'data' => ['authorization_url', 'access_code', 'reference']]
     * Throws on failure (same as PaystackService).
     */
    public function initialize(string $email, float $amount, string $reference, array $meta = []): array
    {
        if ($p = $this->problem()) {
            throw new \Exception($p);
        }

        $amountKobo = (int) round($amount * 100);
        $returnUrl  = route('payment.opay.return', ['reference' => $reference]);

        $payload = [
            'country'     => 'NG',
            'reference'   => $reference,
            'amount'      => ['total' => $amountKobo, 'currency' => 'NGN'],
            'returnUrl'   => $returnUrl,
            'cancelUrl'   => $returnUrl,
            'callbackUrl' => route('payment.opay.webhook'),
            'expireAt'    => 30,
            'product'     => [
                'name'        => mb_substr((string) ($meta['product_name'] ?? 'GozakMart order'), 0, 100),
                'description' => mb_substr((string) ($meta['product_description'] ?? ('GozakMart order ' . $reference)), 0, 200),
            ],
            'userInfo' => array_filter([
                'userEmail'  => $email,
                'userId'     => isset($meta['user_id']) ? (string) $meta['user_id'] : null,
                'userName'   => $meta['customer_name'] ?? null,
                'userMobile' => $meta['phone'] ?? null,
            ]),
        ];

        $res = Http::withToken((string) $this->publicKey)
            ->withHeaders(['MerchantId' => (string) $this->merchantId])
            ->acceptJson()->asJson()->timeout(30)
            ->post($this->base() . '/api/v1/international/cashier/create', $payload);

        $j = $res->json() ?? [];
        if ($res->successful() && ($j['code'] ?? '') === '00000' && !empty($j['data']['cashierUrl'])) {
            return ['status' => true, 'data' => [
                'authorization_url' => (string) $j['data']['cashierUrl'],
                'access_code'       => (string) ($j['data']['orderNo'] ?? ''),
                'reference'         => $reference,
            ]];
        }

        Log::warning('OPay cashier create refused', ['reference' => $reference, 'code' => $j['code'] ?? $res->status(), 'message' => $j['message'] ?? null]);
        throw new \Exception('OPay could not start the payment' . (!empty($j['message']) ? ': ' . $j['message'] : '.'));
    }

    /** Raw cashier status call. */
    public function status(string $reference): array
    {
        $json = self::body(['country' => 'NG', 'reference' => $reference]);
        $res  = Http::withToken(self::sign($json, (string) $this->secretKey))
            ->withHeaders(['MerchantId' => (string) $this->merchantId])
            ->acceptJson()->timeout(30)->withBody($json, 'application/json')
            ->post($this->base() . '/api/v1/international/cashier/status');

        return ['http' => $res->status(), 'json' => $res->json() ?? []];
    }

    /**
     * Ask OPay for the real state of a payment, normalised to Paystack's verify shape:
     * ['status' => true, 'data' => ['status' => success|failed|abandoned|pending, 'amount' (kobo), ...]]
     * Throws when OPay can't answer.
     */
    public function verify(string $reference): array
    {
        if (!$this->merchantId || !$this->secretKey) {
            throw new \Exception('OPay keys are missing.');
        }

        $r = $this->status($reference);
        $j = $r['json'];
        if (($j['code'] ?? '') !== '00000' || empty($j['data'])) {
            throw new \Exception('OPay verification failed: ' . ($j['message'] ?? ('HTTP ' . $r['http'])));
        }

        $d   = $j['data'];
        $map = ['SUCCESS' => 'success', 'FAIL' => 'failed', 'CLOSE' => 'abandoned', 'INITIAL' => 'pending', 'PENDING' => 'pending'];
        $st  = strtoupper((string) ($d['status'] ?? ''));

        return ['status' => true, 'data' => [
            'id'               => $d['orderNo'] ?? null,
            'status'           => $map[$st] ?? 'pending',
            'reference'        => (string) ($d['reference'] ?? $reference),
            'amount'           => (int) ($d['amount']['total'] ?? 0),
            'currency'         => (string) ($d['amount']['currency'] ?? 'NGN'),
            'channel'          => 'opay',
            'gateway'          => 'opay',
            'gateway_response' => $st === 'SUCCESS' ? 'Approved by OPay' : ('OPay status: ' . ($st ?: 'unknown')),
            'raw'              => $d,
        ]];
    }

    /**
     * Check a callback's sha512 field. We never trust the callback on its own —
     * the webhook handler always re-queries the status.
     */
    public function validCallback(array $body): bool
    {
        $p   = (array) ($body['payload'] ?? []);
        $sig = strtolower((string) ($body['sha512'] ?? ''));
        if (!$this->secretKey || $sig === '' || !$p) return false;

        $str = sprintf('{Amount:"%s",Currency:"%s",Reference:"%s",Refunded:%s,Status:"%s",Timestamp:"%s",Token:"%s",TransactionID:"%s"}',
            $p['amount'] ?? '', $p['currency'] ?? '', $p['reference'] ?? '', !empty($p['refunded']) ? 't' : 'f',
            $p['status'] ?? '', $p['timestamp'] ?? '', $p['token'] ?? '', $p['transactionId'] ?? '');

        foreach (['sha3-512', 'sha512'] as $algo) {
            if (in_array($algo, hash_hmac_algos(), true) && hash_equals(hash_hmac($algo, $str, $this->secretKey), $sig)) {
                return true;
            }
        }
        return false;
    }

    /** Connection test for the admin page: look up a reference that doesn't exist. */
    public static function testKeys(string $set, ?string $merchantId, ?string $secret): array
    {
        $json = self::body(['country' => 'NG', 'reference' => 'GZK-KEYTEST-' . time()]);
        $res  = Http::withToken(self::sign($json, (string) $secret))
            ->withHeaders(['MerchantId' => (string) $merchantId])
            ->acceptJson()->timeout(20)->withBody($json, 'application/json')
            ->post(($set === 'live' ? self::LIVE : self::TEST) . '/api/v1/international/cashier/status');

        $code = (string) ($res->json('code') ?? '');
        if (in_array($code, ['00000', '02006'], true)) return ['success' => true];

        return ['success' => false, 'plain' => true,
                'message' => 'OPay refused the keys' . ($res->json('message') ? ': ' . $res->json('message') : ' (HTTP ' . $res->status() . ').')];
    }
}
