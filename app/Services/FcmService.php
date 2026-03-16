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

    /**
     * Send notification to a single FCM token
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

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Simple alias used by FcmTestController
     */
    public function send(string $token, string $title, string $body, array $data = []): array
    {
        return $this->sendToToken($token, $title, $body, $data);
    }

    // ── Token generation ────────────────────────────────────────────────────

    protected function getAccessToken(): string
    {
        // Cache for 55 minutes (token lasts 60)
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
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $now    = time();

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
        // Ensure all data values are strings (FCM requirement)
        $stringData = array_map('strval', array_merge([
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'timestamp'    => now()->toISOString(),
        ], $data));

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
                        'channel_id' => 'lightning_deals',
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
