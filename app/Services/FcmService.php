<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $url;
    protected array $serviceAccount;
    protected string $projectId;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID'));
        $this->url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $this->initializeServiceAccount();
    }

    protected function initializeServiceAccount(): void
    {
        $credentialsPath = env('FIREBASE_CREDENTIALS');

        if (!$credentialsPath) {
            throw new Exception('FIREBASE_CREDENTIALS env variable is not set');
        }

        $serviceAccountPath = base_path($credentialsPath);

        if (!file_exists($serviceAccountPath)) {
            throw new Exception('Firebase service account file not found: ' . $serviceAccountPath);
        }

        $this->serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON in Firebase service account file');
        }
    }

    // ── Core send ───────────────────────────────────────────────────────────

    /**
     * Send notification to a single FCM token.
     * All other methods ultimately call this.
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $payload     = $this->buildPayload($token, $title, $body, $data);

            $response = Http::timeout(15)
                ->retry(2, 100)
                ->withToken($accessToken)
                ->post($this->url, $payload);

            $responseData = $response->json();
            $success      = $response->successful();

            if (!$success) {
                Log::error('FCM API error', [
                    'status'       => $response->status(),
                    'response'     => $responseData,
                    'token_prefix' => substr($token, 0, 10),
                ]);
            }

            return [
                'success'  => $success,
                'response' => $responseData,
                'status'   => $response->status(),
            ];
        } catch (Exception $e) {
            Log::error('FCM send failed: ' . $e->getMessage(), [
                'token_prefix' => substr($token, 0, 10),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /** Alias used by FcmTestController */
    public function send(string $token, string $title, string $body, array $data = []): array
    {
        return $this->sendToToken($token, $title, $body, $data);
    }

    // ── Wrappers called by NotificationService ───────────────────────────────

    /**
     * Send to a User model directly.
     * Called by NotificationService::sendToUser() and sendSecurityAlert().
     */
    public function sendToUser(\App\Models\User $user, string $title, string $body, array $data = [], string $type = 'general'): array
    {
        if (empty($user->fcm_token)) {
            return ['success' => false, 'error' => 'User has no FCM token'];
        }
        $data['type'] = $data['type'] ?? $type;
        return $this->sendToToken($user->fcm_token, $title, $body, $data);
    }

    /**
     * Send order placed / confirmation notification.
     * Called by NotificationService::sendOrderConfirmation().
     */
    public function sendOrderConfirmation(\App\Models\Order $order): array
    {
        $user = $order->user;
        if (!$user || empty($user->fcm_token)) {
            return ['success' => false, 'error' => 'User has no FCM token'];
        }

        $invoiceRef = $order->invoice_number ?? substr($order->id, 0, 8);
        $total      = number_format($order->total_amount, 0);

        return $this->sendToToken(
            $user->fcm_token,
            '🛍️ Order Confirmed!',
            "Order #{$invoiceRef} placed for ₦{$total}. We'll update you when it ships.",
            [
                'type'           => 'order_placed',
                'order_id'       => (string) $order->id,
                'invoice_number' => (string) ($order->invoice_number ?? ''),
                'status'         => 'pending',
                'total_amount'   => (string) $order->total_amount,
            ]
        );
    }

    /**
     * Send order status update notification.
     * Called by NotificationService::sendOrderStatusUpdate()
     * and directly by OrderNotificationService.
     */
    public function sendOrderStatusUpdate(\App\Models\Order $order, string $newStatus): array
    {
        $user = $order->user;
        if (!$user || empty($user->fcm_token)) {
            return ['success' => false, 'error' => 'User has no FCM token'];
        }

        $emojis = [
            'pending'    => '🕐',
            'processing' => '⚙️',
            'shipped'    => '🚚',
            'delivered'  => '✅',
            'cancelled'  => '❌',
        ];
        $titles = [
            'pending'    => 'Order Received',
            'processing' => 'Order Processing',
            'shipped'    => 'Order Shipped!',
            'delivered'  => 'Order Delivered!',
            'cancelled'  => 'Order Cancelled',
        ];
        $bodies = [
            'pending'    => 'Your order is being reviewed.',
            'processing' => 'Your order is being prepared for shipment.',
            'shipped'    => 'Your order is on its way! Track it in the app.',
            'delivered'  => 'Your order has been delivered. Enjoy! 🎉',
            'cancelled'  => 'Your order has been cancelled. Contact support if needed.',
        ];

        $emoji       = $emojis[$newStatus]  ?? '📦';
        $statusTitle = $titles[$newStatus]  ?? 'Order Update';
        $statusBody  = $bodies[$newStatus]  ?? "Your order status is now: {$newStatus}.";
        $invoiceRef  = $order->invoice_number ?? substr($order->id, 0, 8);

        return $this->sendToToken(
            $user->fcm_token,
            "{$emoji} {$statusTitle}",
            "Order #{$invoiceRef}: {$statusBody}",
            [
                'type'           => 'order_status_update',
                'order_id'       => (string) $order->id,
                'invoice_number' => (string) ($order->invoice_number ?? ''),
                'status'         => $newStatus,
                'total_amount'   => (string) $order->total_amount,
            ]
        );
    }

    /**
     * Send promotional notification to a user.
     * Called by NotificationService::sendPromotionalNotification().
     */
    public function sendPromotionalNotification(\App\Models\User $user, string $title, string $body, array $data = []): array
    {
        if (empty($user->fcm_token)) {
            return ['success' => false, 'error' => 'User has no FCM token'];
        }
        $data['type'] = $data['type'] ?? 'promotional';
        return $this->sendToToken($user->fcm_token, $title, $body, $data);
    }

    // ── Token generation ────────────────────────────────────────────────────

    protected function getAccessToken(): string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            return $this->generateAccessToken();
        });
    }

    protected function generateAccessToken(): string
    {
        $jwt = $this->generateJWT();

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (!$response->successful()) {
            Log::error('FCM token generation failed', $response->json());
            throw new Exception('Failed to generate FCM access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    protected function generateJWT(): string
    {
        $header  = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now     = time();
        $payload = [
            'iss'   => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ];

        $headerBase64  = $this->base64UrlEncode(json_encode($header));
        $payloadBase64 = $this->base64UrlEncode(json_encode($payload));
        $signature     = $this->signData("{$headerBase64}.{$payloadBase64}");

        return "{$headerBase64}.{$payloadBase64}.{$this->base64UrlEncode($signature)}";
    }

    protected function signData(string $data): string
    {
        openssl_sign($data, $signature, $this->serviceAccount['private_key'], 'SHA256');
        return $signature;
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // ── Payload builder ─────────────────────────────────────────────────────

    protected function buildPayload(string $token, string $title, string $body, array $data = []): array
    {
        $stringData = array_map('strval', array_merge([
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'timestamp'    => now()->toISOString(),
        ], $data));

        // Route to correct Android channel based on notification type
        $type      = $stringData['type'] ?? 'general';
        $channelId = str_starts_with($type, 'order_') ? 'order_updates' : 'lightning_deals';

        return [
            'message' => [
                'token'        => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'android' => [
                    'priority'     => 'high',
                    'notification' => [
                        'channel_id' => $channelId,
                        'tag'        => uniqid('notif_', true),
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
                'data' => $stringData,
            ],
        ];
    }
}
