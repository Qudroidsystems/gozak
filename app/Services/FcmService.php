<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FcmService
{
    protected $url;
    protected $credentials;

    public function __construct()
    {
        // Use environment variable for project ID
        $projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID'));
        $this->url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        $this->initializeCredentials();
    }

    protected function initializeCredentials()
    {
        try {
            $serviceAccountPath = storage_path('app/firebase/shoppingapp-10dee-c547ed96bdf5.json');
            
            if (!file_exists($serviceAccountPath)) {
                throw new Exception('Firebase service account file not found');
            }

            $this->credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $serviceAccountPath
            );
        } catch (Exception $e) {
            Log::error('FCM Service initialization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function send($deviceToken, $title, $body, array $data = [])
    {
        try {
            $accessToken = $this->getAccessToken();
            
            $payload = $this->buildPayload($deviceToken, $title, $body, $data);

            $response = Http::timeout(30)
                ->retry(3, 100)
                ->withToken($accessToken)
                ->post($this->url, $payload);

            if (!$response->successful()) {
                Log::error('FCM API error', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'device_token' => $deviceToken
                ]);
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('FCM send failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    protected function getAccessToken()
    {
        try {
            $authToken = $this->credentials->fetchAuthToken();
            return $authToken['access_token'] ?? null;
        } catch (Exception $e) {
            Log::error('FCM access token fetch failed: ' . $e->getMessage());
            throw new Exception('Failed to obtain FCM access token');
        }
    }

    protected function buildPayload($deviceToken, $title, $body, array $data = [])
    {
        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'android' => [
                    'priority' => 'high'
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'content-available' => 1,
                            'sound' => 'default'
                        ]
                    ]
                ]
            ]
        ];

        // Add data payload if provided
        if (!empty($data)) {
            $payload['message']['data'] = $data;
        }

        return $payload;
    }

    /**
     * Send to multiple devices
     */
    public function sendMulticast(array $deviceTokens, $title, $body, array $data = [])
    {
        $results = [];
        
        foreach ($deviceTokens as $token) {
            $results[$token] = $this->send($token, $title, $body, $data);
        }
        
        return $results;
    }

    /**
     * Send with additional options
     */
    public function sendWithOptions($deviceToken, $title, $body, array $options = [])
    {
        $defaultOptions = [
            'data' => [],
            'image' => null,
            'click_action' => null,
            'priority' => 'high',
        ];

        $options = array_merge($defaultOptions, $options);
        
        $payload = $this->buildPayload($deviceToken, $title, $body, $options['data']);
        
        // Add image if provided
        if ($options['image']) {
            $payload['message']['notification']['image'] = $options['image'];
        }
        
        // Add click action if provided
        if ($options['click_action']) {
            $payload['message']['notification']['click_action'] = $options['click_action'];
        }

        try {
            $accessToken = $this->getAccessToken();
            
            $response = Http::withToken($accessToken)
                ->post($this->url, $payload);

            return $response->json();

        } catch (Exception $e) {
            Log::error('FCM send with options failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}