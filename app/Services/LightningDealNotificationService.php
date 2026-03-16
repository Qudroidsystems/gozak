<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class LightningDealNotificationService
{
    protected FcmService $fcm;

    public function __construct(FcmService $fcm)
    {
        $this->fcm = $fcm;
    }

    /**
     * Notify all users about new lightning deal(s).
     *
     * Single deal  → deep-links to the product detail screen.
     * Multiple deals → deep-links to the lightning deals list screen.
     *
     * @param  array  $deals  Each item must have:
     *                        deal_id, product_id, title,
     *                        discounted_price, discount_percentage, thumbnail
     * @return array  ['sent' => int, 'failed' => int]
     */
    public function notifyNewDeals(array $deals): array
    {
        if (empty($deals)) {
            return ['sent' => 0, 'failed' => 0];
        }

        $isSingle = count($deals) === 1;
        $deal     = $deals[0];

        // ── Build notification content ──────────────────────────────────
        if ($isSingle) {
            $title = '⚡ Lightning Deal!';
            $body  = "{$deal['title']} — {$deal['discount_percentage']}% OFF! "
                   . 'Now ₦' . number_format($deal['discounted_price'], 0);

            $data = [
                'type'                => 'lightning_deal_single',
                'product_id'          => (string) $deal['product_id'],
                'deal_id'             => (string) ($deal['deal_id'] ?? ''),
                'title'               => (string) $deal['title'],
                'discounted_price'    => (string) $deal['discounted_price'],
                'discount_percentage' => (string) $deal['discount_percentage'],
                'thumbnail'           => (string) ($deal['thumbnail'] ?? ''),
                'route'               => '/lightning-deals/product',
            ];
        } else {
            $count = count($deals);
            $title = "⚡ {$count} New Lightning Deals!";
            $body  = 'Hurry! Limited-time deals just dropped. Tap to see all.';

            $data = [
                'type'  => 'lightning_deal_multiple',
                'count' => (string) $count,
                'route' => '/lightning-deals',
            ];
        }

        // ── Fetch tokens (only users who have notifications enabled) ────
        $tokens = User::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->where('push_notifications_enabled', true)
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            Log::info('LightningDealNotification: no eligible FCM tokens found');
            return ['sent' => 0, 'failed' => 0];
        }

        // ── Send to every token ─────────────────────────────────────────
        $sent   = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            $result = $this->fcm->sendToToken($token, $title, $body, $data);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;

                // Log stale / invalid tokens for cleanup
                $errorCode = $result['response']['error']['status'] ?? '';
                if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                    User::where('fcm_token', $token)
                        ->update(['fcm_token' => null]);
                    Log::info('LightningDealNotification: stale token cleared', [
                        'token_prefix' => substr($token, 0, 10),
                    ]);
                } else {
                    Log::warning('LightningDealNotification: send failed', [
                        'token_prefix' => substr($token, 0, 10),
                        'error'        => $result['error'] ?? ($result['response'] ?? 'unknown'),
                    ]);
                }
            }
        }

        Log::info("LightningDealNotification: sent={$sent}, failed={$failed}");

        return ['sent' => $sent, 'failed' => $failed];
    }
}
