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
        $serviceAccountPath = base_path(env('FIREBASE_CREDENTIALS'));

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
                    'token_prefix' => substr($token, 0, 10)
                ]);
            }

            return [
                'success' => $success,
                'response' => $responseData,
                'status' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('FCM send failed: ' . $e->getMessage(), [
                'token_prefix' => substr($token, 0, 10)
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function getAccessToken(): string
    {
        return Cache::remember('fcm_access_token', 3600, function () {
            return $this->generateAccessToken();
        });
    }

    protected function generateAccessToken(): string
    {
        $jwt = $this->generateJWT();

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);

        if (!$response->successful()) {
            Log::error('FCM Token generation failed', $response->json());
            throw new Exception('Failed to generate access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }

    protected function generateJWT(): string
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
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

    protected function signData(string $data): string
    {
        $privateKey = $this->serviceAccount['private_key'];
        openssl_sign($data, $signature, $privateKey, 'SHA256');
        return $signature;
    }

    protected function base64UrlEncode(string $data): string
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
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'order_updates',
                    'tag' => uniqid('notif_', true), // 🔥 THIS FIXES IT
                ],
            ],
            'data' => array_merge([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'timestamp' => now()->toISOString(),
            ], $data),
        ],
    ];
}


    /**
    * Simple send method (used by test controller)
    */
    public function send(string $token, string $title, string $body, array $data = []): array
    {
        return $this->sendToToken($token, $title, $body, $data);
    }

}
