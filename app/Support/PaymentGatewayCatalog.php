<?php

namespace App\Support;

/**
 * The payment gateways GozakMart knows about and the credentials each one
 * needs per credential set ("test" = sandbox, "live"). Drives the admin
 * Payment Gateways page, validation and the connection tests.
 * Ported from the CSS Kabba portal.
 */
class PaymentGatewayCatalog
{
    public const PROVIDERS = [
        'paystack' => [
            'name'      => 'Paystack',
            'supported' => true,
            'used_for'  => 'App checkout (cards, bank, USSD)',
            'dashboard' => 'https://dashboard.paystack.com/#/settings/developers',
            'fields'    => [
                'secret_key' => ['label' => 'Secret key', 'secret' => true,  'required' => true,
                                 'prefix' => ['test' => 'sk_test_', 'live' => 'sk_live_']],
                'public_key' => ['label' => 'Public key', 'secret' => false, 'required' => true,
                                 'prefix' => ['test' => 'pk_test_', 'live' => 'pk_live_']],
            ],
        ],
        'opay' => [
            'name'      => 'OPay',
            'supported' => true,
            'used_for'  => 'App checkout (OPay wallet, cards, bank transfer)',
            'dashboard' => 'https://merchant.opaycheckout.com',
            'fields'    => [
                'merchant_id' => ['label' => 'Merchant ID', 'secret' => false, 'required' => true],
                'public_key'  => ['label' => 'Public key', 'secret' => false, 'required' => true],
                'secret_key'  => ['label' => 'Private (secret) key', 'secret' => true, 'required' => true],
            ],
        ],
    ];

    public static function get(string $provider): ?array
    {
        return self::PROVIDERS[$provider] ?? null;
    }

    public static function fields(string $provider): array
    {
        return self::PROVIDERS[$provider]['fields'] ?? [];
    }

    /** "sandbox"/"live" (DB value) → "test"/"live" (credential set). */
    public static function set(?string $mode): string
    {
        return $mode === 'live' ? 'live' : 'test';
    }
}
