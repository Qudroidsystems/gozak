<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PaystackService
{
    protected $secretKey;
    protected $publicKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->publicKey = config('services.paystack.public_key');
        $this->baseUrl = config('services.paystack.payment_url');
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
        if (config('services.paystack.callback_url')) {
            $data['callback_url'] = config('services.paystack.callback_url');
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