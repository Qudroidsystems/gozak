<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class PaystackService
{
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl;

   public function __construct()
{
    $this->secretKey = Config::get('services.paystack.secret_key');
    $this->publicKey = Config::get('services.paystack.public_key');
    $this->baseUrl = Config::get('services.paystack.payment_url', 'https://api.paystack.co');
    
    // Temporary debug logging
    Log::info('PaystackService initialized', [
        'secret_key_prefix' => substr($this->secretKey, 0, 10) . '...',
        'public_key_prefix' => substr($this->publicKey, 0, 10) . '...',
        'base_url' => $this->baseUrl,
        'secret_key_length' => strlen($this->secretKey),
        'is_empty' => empty($this->secretKey)
    ]);
    
    if (empty($this->secretKey)) {
        Log::error('Paystack secret key is empty!');
        throw new \Exception('Paystack secret key not configured');
    }
}

    /**
     * Initialize a payment transaction
     */
    public function initializePayment($email, $amount, $reference = null, $metadata = [])
    {
        $url = $this->baseUrl . '/transaction/initialize';

        $data = [
            'email' => $email,
            'amount' => $amount * 100, // Convert to kobo (1 Naira = 100 kobo)
            'reference' => $reference ?? $this->generateReference(),
            'metadata' => $metadata,
            'currency' => 'NGN', // Nigerian Naira
        ];

        // Add callback URL if configured
        if (Config::get('services.paystack.callback_url')) {
            $data['callback_url'] = Config::get('services.paystack.callback_url');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($url, $data);

            if ($response->successful()) {
                Log::info('Paystack: Payment initialized', [
                    'reference' => $data['reference'],
                    'amount' => $amount
                ]);
                return $response->json();
            }

            Log::error('Paystack: Payment initialization failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('Payment initialization failed: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Paystack: Exception during initialization', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Charge a card directly (with PIN)
     */
    public function chargeCard($data)
    {
        $url = $this->baseUrl . '/transaction/charge';

        // Prepare card details
        $cardData = $data['card'];
        $payload = [
            'email' => $data['email'],
            'amount' => $data['amount'] * 100, // Convert to kobo
            'card' => [
                'number' => $cardData['number'],
                'cvv' => $cardData['cvv'],
                'expiry_month' => $cardData['expiry_month'],
                'expiry_year' => $cardData['expiry_year'], // Already formatted as 2 digits
                'pin' => $cardData['pin'],
            ],
        ];

        // Add metadata if present
        if (isset($data['metadata'])) {
            $payload['metadata'] = $data['metadata'];
        }

        Log::info('Paystack charge attempt', [
            'url' => $url,
            'email' => $payload['email'],
            'amount' => $payload['amount'],
            'card_last4' => substr($cardData['number'], -4)
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
            ])->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->json();

            Log::info('Paystack charge response', [
                'status_code' => $statusCode,
                'response' => $responseBody
            ]);

            if ($response->successful() && isset($responseBody['status']) && $responseBody['status'] === true) {
                return $responseBody;
            }

            // Log error details
            Log::error('Paystack charge failed', [
                'status' => $statusCode,
                'response' => $responseBody,
                'message' => $responseBody['message'] ?? 'Unknown error'
            ]);

            throw new \Exception($responseBody['message'] ?? 'Card charge failed');

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Paystack HTTP exception', [
                'error' => $e->getMessage(),
                'response' => $e->response->body() ?? null
            ]);
            throw new \Exception('Payment gateway error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Paystack exception during card charge', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Submit OTP for transaction authorization
     */
    public function submitOtp($reference, $otp)
    {
        $url = $this->baseUrl . '/charge_authorization';

        $payload = [
            'reference' => $reference,
            'otp' => $otp
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Paystack: OTP submission successful', [
                    'reference' => $reference,
                    'status' => $result['data']['status'] ?? 'unknown'
                ]);
                return $result;
            }

            Log::error('Paystack: OTP submission failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'request_data' => $payload
            ]);

            throw new \Exception('OTP submission failed: ' . ($response->json()['message'] ?? $response->body()));

        } catch (\Exception $e) {
            Log::error('Paystack: Exception during OTP submission', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);
            throw $e;
        }
    }

    /**
     * Submit PIN for transaction (typically part of charge, but for re-auth if needed)
     */
    public function submitPin($reference, $pin)
    {
        // Note: PIN is usually submitted during initial charge. This method can be used for re-authorization if needed.
        // For standard flow, it re-charges with PIN if previous attempt required it.
        $url = $this->baseUrl . '/transaction/charge_authorization'; // Or use /transaction/charge if re-charging

        $payload = [
            'reference' => $reference,
            'pin' => $pin
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Paystack: PIN submission successful', [
                    'reference' => $reference,
                    'status' => $result['data']['status'] ?? 'unknown'
                ]);
                return $result;
            }

            Log::error('Paystack: PIN submission failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'request_data' => $payload
            ]);

            throw new \Exception('PIN submission failed: ' . ($response->json()['message'] ?? $response->body()));

        } catch (\Exception $e) {
            Log::error('Paystack: Exception during PIN submission', [
                'error' => $e->getMessage(),
                'reference' => $reference
            ]);
            throw $e;
        }
    }

    /**
     * Verify a payment transaction
     */
    public function verifyPayment($reference)
    {
        $url = $this->baseUrl . '/transaction/verify/' . $reference;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($url);

            if ($response->successful()) {
                Log::info('Paystack: Payment verified', [
                    'reference' => $reference,
                    'status' => $response->json()['data']['status'] ?? 'unknown'
                ]);
                return $response->json();
            }

            Log::error('Paystack: Payment verification failed', [
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('Payment verification failed: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Paystack: Exception during verification', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction($id)
    {
        $url = $this->baseUrl . '/transaction/' . $id;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('Failed to fetch transaction: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Paystack: Failed to get transaction', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique payment reference
     */
    public function generateReference()
    {
        return 'PAY_' . time() . '_' . uniqid();
    }

    /**
     * Get public key for frontend
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($signature, $body)
    {
        $expectedSignature = hash_hmac('sha512', $body, $this->secretKey);
        return $signature === $expectedSignature;
    }
}