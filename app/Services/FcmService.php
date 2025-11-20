<?php

namespace App\Services;

use Exception;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FcmService
{
    protected $url;
    protected $serviceAccount;
    protected $projectId;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID'));
        $this->url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $this->initializeServiceAccount();
    }

    protected function initializeServiceAccount()
    {
        try {
            $serviceAccountPath = storage_path('app/firebase/shoppingapp-10dee-c547ed96bdf5.json');
            
            if (!file_exists($serviceAccountPath)) {
                throw new Exception('Firebase service account file not found at: ' . $serviceAccountPath);
            }

            $this->serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in service account file');
            }

        } catch (Exception $e) {
            Log::error('FCM Service initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send notification to user with preference checking
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], string $type = 'general'): array
    {
        // Check user preferences
        if (!$user->canReceivePushNotifications($type)) {
            Log::info("User {$user->id} cannot receive {$type} notifications due to preferences or quiet hours");
            return ['skipped' => true, 'reason' => 'user_preferences'];
        }

        $results = [];
        $tokens = $user->getActiveFcmTokens();

        if (empty($tokens)) {
            Log::warning("User {$user->id} has no active FCM tokens");
            return ['error' => 'no_active_tokens'];
        }

        foreach ($tokens as $token) {
            $results[] = $this->sendToToken($token, $title, $body, $data, $user->id);
        }

        // Update user stats
        $user->recordNotificationSent();

        return [
            'success' => true,
            'sent_to_devices' => count($tokens),
            'results' => $results,
        ];
    }

    /**
     * Send order status update notification
     */
    public function sendOrderStatusUpdate(Order $order, string $newStatus): array
    {
        $user = $order->user;
        $messages = $this->getOrderStatusMessages($order, $newStatus);

        return $this->sendToUser(
            $user,
            $messages['title'],
            $messages['body'],
            [
                'type' => 'order_status_update',
                'order_id' => $order->id,
                'status' => $newStatus,
                'screen' => 'order_details',
                'action' => 'view_order',
                'timestamp' => now()->toISOString(),
            ],
            'order_update'
        );
    }

    /**
     * Send promotional notification
     */
    public function sendPromotionalNotification(User $user, string $title, string $body, array $data = []): array
    {
        return $this->sendToUser(
            $user,
            $title,
            $body,
            array_merge($data, [
                'type' => 'promotional',
                'screen' => 'promotions',
                'action' => 'view_promotion',
            ]),
            'promotional'
        );
    }

    /**
     * Send to specific token with better error handling
     */
    public function sendToToken(string $token, string $title, string $body, array $data = [], ?int $userId = null): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $payload = $this->buildPayload($token, $title, $body, $data);

            $response = Http::timeout(15)
                ->retry(2, 100)
                ->withToken($accessToken)
                ->post($this->url, $payload);

            $responseData = $response->json();
            $success = $response->successful();

            if (!$success) {
                Log::error('FCM API error', [
                    'status' => $response->status(),
                    'response' => $responseData,
                    'user_id' => $userId,
                    'token_prefix' => substr($token, 0, 10)
                ]);

                // Handle specific error cases
                if (in_array($response->status(), [404, 400])) {
                    $this->handleInvalidToken($token, $userId);
                }

                return [
                    'success' => false,
                    'error' => $responseData['error']['message'] ?? 'Unknown error',
                    'status' => $response->status(),
                ];
            }

            Log::debug('FCM notification sent successfully', [
                'user_id' => $userId,
                'message_id' => $responseData['name'] ?? null,
            ]);

            return [
                'success' => true,
                'message_id' => $responseData['name'] ?? null,
                'response' => $responseData,
            ];

        } catch (Exception $e) {
            Log::error('FCM send failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'token_prefix' => substr($token, 0, 10)
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get templated messages for order status updates
     */
    protected function getOrderStatusMessages(Order $order, string $status): array
    {
        $orderIdShort = substr($order->id, -8);
        $messages = [
            'pending' => [
                'title' => "Order #{$orderIdShort} Received",
                'body' => "We've received your order and are preparing it for shipment.",
            ],
            'processing' => [
                'title' => "Order #{$orderIdShort} Processing",
                'body' => "Your order is being processed and will be shipped soon.",
            ],
            'shipped' => [
                'title' => "Order #{$orderIdShort} Shipped!",
                'body' => "Your order has been shipped and is on its way to you.",
            ],
            'delivered' => [
                'title' => "Order #{$orderIdShort} Delivered!",
                'body' => "Your order has been delivered. We hope you love it!",
            ],
            'cancelled' => [
                'title' => "Order #{$orderIdShort} Cancelled",
                'body' => "Your order has been cancelled as requested.",
            ],
        ];

        return $messages[$status] ?? [
            'title' => "Order #{$orderIdShort} Updated",
            'body' => "Your order status has been updated to " . ucfirst($status),
        ];
    }

    protected function getAccessToken()
    {
        return Cache::remember('fcm_access_token', 3600, function () {
            return $this->generateAccessToken();
        });
    }

    protected function generateAccessToken()
    {
        $jwt = $this->generateJWT();
        
        $response = Http::asForm()
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

        if (!$response->successful()) {
            Log::error('FCM Token generation failed', $response->json());
            throw new Exception('Failed to generate access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    protected function generateJWT()
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        $now = time();
        $payload = [
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ];

        $headerBase64 = $this->base64UrlEncode(json_encode($header));
        $payloadBase64 = $this->base64UrlEncode(json_encode($payload));
        
        $signature = $this->signData("{$headerBase64}.{$payloadBase64}");
        $signatureBase64 = $this->base64UrlEncode($signature);

        return "{$headerBase64}.{$payloadBase64}.{$signatureBase64}";
    }

    protected function signData($data)
    {
        $privateKey = $this->serviceAccount['private_key'];
        
        openssl_sign($data, $signature, $privateKey, 'SHA256');
        
        return $signature;
    }

    protected function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function buildPayload(string $token, string $title, string $body, array $data = []): array
    {
        return [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'image' => $data['image_url'] ?? null,
                ],
                'data' => array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'type' => 'general',
                    'timestamp' => now()->toISOString(),
                ], $data),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'order_updates',
                        'icon' => 'notification_icon',
                        'color' => '#FF6B35',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                ],
                'webpush' => [
                    'headers' => [
                        'Urgency' => 'high',
                    ],
                ],
            ],
        ];
    }

    /**
     * Handle invalid token by removing it from user's tokens
     */
    protected function handleInvalidToken(string $token, ?int $userId): void
    {
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                // Find which device this token belongs to and remove it
                $tokens = $user->fcm_tokens ?? [];
                $updatedTokens = array_filter($tokens, function($item) use ($token) {
                    return $item['token'] !== $token;
                });
                
                if (count($updatedTokens) < count($tokens)) {
                    $user->update(['fcm_tokens' => $updatedTokens]);
                    Log::warning("Invalid FCM token removed for user {$userId}");
                }
            }
        }
    }

    /**
     * Clean up expired tokens (run this periodically)
     */
    public function cleanupExpiredTokens(int $days = 30): int
    {
        $cutoffDate = now()->subDays($days);
        $users = User::whereNotNull('fcm_tokens')->get();
        $removedCount = 0;

        foreach ($users as $user) {
            $tokens = $user->fcm_tokens ?? [];
            $originalCount = count($tokens);
            
            $updatedTokens = array_filter($tokens, function($token) use ($cutoffDate) {
                $lastUsed = $token['last_used_at'] ?? $token['added_at'];
                return strtotime($lastUsed) >= $cutoffDate->timestamp;
            });
            
            if (count($updatedTokens) < $originalCount) {
                $user->update(['fcm_tokens' => $updatedTokens]);
                $removedCount += ($originalCount - count($updatedTokens));
            }
        }

        Log::info("FCM token cleanup removed {$removedCount} expired tokens");
        return $removedCount;
    }
}