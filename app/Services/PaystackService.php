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

        // Debug logging
        Log::info('PaystackService initialized', [
            'secret_key_prefix' => substr($this->secretKey, 0, 10) . '...',
            'public_key_prefix' => substr($this->publicKey, 0, 10) . '...',
            'base_url' => $this->baseUrl,
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

        // Convert amount to kobo (integer) - FIXED
        $amountInKobo = (int) round($amount * 100);

        // Log for debugging
        Log::info('Paystack: Amount conversion for initialization', [
            'original_amount' => $amount,
            'amount_in_kobo' => $amountInKobo,
            'is_integer' => is_int($amountInKobo),
            'email' => $email
        ]);

        $data = [
            'email' => $email,
            'amount' => $amountInKobo, // Use integer value in kobo
            'reference' => $reference ?? $this->generateReference(),
            'metadata' => $metadata,
            'currency' => 'NGN',
        ];

        // Add callback URL if configured
        if ($callbackUrl = Config::get('services.paystack.callback_url')) {
            $data['callback_url'] = $callbackUrl;
        }

        try {
            Log::info('Paystack: Sending initialization request', [
                'url' => $url,
                'data' => $data
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($url, $data);

            $responseBody = $response->json();

            if ($response->successful()) {
                Log::info('Paystack: Payment initialized successfully', [
                    'reference' => $data['reference'],
                    'original_amount' => $amount,
                    'amount_sent' => $amountInKobo,
                    'response_reference' => $responseBody['data']['reference'] ?? null
                ]);
                return $responseBody;
            }

            Log::error('Paystack: Payment initialization failed', [
                'status_code' => $response->status(),
                'response_body' => $responseBody,
                'request_data' => $data
            ]);

            throw new \Exception('Payment initialization failed: ' . json_encode($responseBody));

        } catch (\Exception $e) {
            Log::error('Paystack: Exception during initialization', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data
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

        // Convert amount to kobo - FIXED
        $amountInKobo = (int) round($data['amount'] * 100);

        // Prepare card details
        $cardData = $data['card'];

        // Ensure expiry_year is 2 digits
        $expiryYear = $cardData['expiry_year'];
        if (strlen($expiryYear) > 2) {
            $expiryYear = substr($expiryYear, -2);
        }

        $payload = [
            'email' => $data['email'],
            'amount' => $amountInKobo, // Use integer value in kobo
            'card' => [
                'number' => $cardData['number'],
                'cvv' => $cardData['cvv'],
                'expiry_month' => $cardData['expiry_month'],
                'expiry_year' => $expiryYear,
                'pin' => $cardData['pin'],
            ],
        ];

        // Add metadata if present
        if (isset($data['metadata'])) {
            $payload['metadata'] = $data['metadata'];
        }

        // IMPORTANT: Add reference if present
        if (isset($data['reference'])) {
            $payload['reference'] = $data['reference'];
        }

        Log::info('Paystack charge attempt', [
            'original_amount' => $data['amount'],
            'amount_in_kobo' => $amountInKobo,
            'is_integer' => is_int($amountInKobo),
            'email' => $data['email']
        ]);

        try {
            // Use PUBLIC key for /transaction/charge endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->publicKey,
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
            ])->timeout(30)->post($url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->json();

            Log::info('Paystack charge response', [
                'status_code' => $statusCode,
                'response_status' => $responseBody['status'] ?? null,
                'response_message' => $responseBody['message'] ?? null,
                'data_status' => $responseBody['data']['status'] ?? null
            ]);

            if ($response->successful() && isset($responseBody['status']) && $responseBody['status'] === true) {
                Log::info('Paystack charge successful', [
                    'reference' => $data['reference'] ?? 'N/A',
                    'data_status' => $responseBody['data']['status'] ?? 'unknown',
                    'amount_charged' => $amountInKobo
                ]);
                return $responseBody;
            }

            // Log detailed error
            Log::error('Paystack charge failed', [
                'status_code' => $statusCode,
                'response_status' => $responseBody['status'] ?? null,
                'message' => $responseBody['message'] ?? 'No message',
                'full_response' => $responseBody
            ]);

            throw new \Exception($responseBody['message'] ?? 'Card charge failed');

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Paystack HTTP exception', [
                'error' => $e->getMessage(),
                'response_body' => $e->response ? $e->response->body() : null
            ]);
            throw new \Exception('Payment gateway error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Paystack exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Submit OTP for transaction authorization
     */
    public function submitOtp($reference, $otp)
    {
        $url = $this->baseUrl . '/transaction/charge_authorization';

        $payload = [
            'reference' => $reference,
            'otp' => $otp
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $responseBody = $response->json();

            if ($response->successful()) {
                Log::info('Paystack: OTP submission successful', [
                    'reference' => $reference,
                    'status' => $responseBody['data']['status'] ?? 'unknown'
                ]);
                return $responseBody;
            }

            Log::error('Paystack: OTP submission failed', [
                'status' => $response->status(),
                'body' => $responseBody,
                'request_data' => $payload
            ]);

            throw new \Exception('OTP submission failed: ' . ($responseBody['message'] ?? json_encode($responseBody)));

        } catch (\Exception $e) {
            Log::error('Paystack: Exception during OTP submission', [
                'error' => $e->getMessage(),
                'reference' => $reference,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Submit PIN for transaction
     */
    public function submitPin($reference, $pin)
    {
        $url = $this->baseUrl . '/transaction/charge_authorization';

        $payload = [
            'reference' => $reference,
            'pin' => $pin
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $responseBody = $response->json();

            if ($response->successful()) {
                Log::info('Paystack: PIN submission successful', [
                    'reference' => $reference,
                    'status' => $responseBody['data']['status'] ?? 'unknown'
                ]);
                return $responseBody;
            }

            Log::error('Paystack: PIN submission failed', [
                'status' => $response->status(),
                'body' => $responseBody,
                'request_data' => $payload
            ]);

            throw new \Exception('PIN submission failed: ' . ($responseBody['message'] ?? json_encode($responseBody)));

        } catch (\Exception $e) {
            Log::error('Paystack: Exception during PIN submission', [
                'error' => $e->getMessage(),
                'reference' => $reference,
                'trace' => $e->getTraceAsString()
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
            Log::info('Paystack: Verifying payment', ['reference' => $reference]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->get($url);

            $responseBody = $response->json();

            if ($response->successful()) {
                Log::info('Paystack: Payment verification successful', [
                    'reference' => $reference,
                    'status' => $responseBody['data']['status'] ?? 'unknown'
                ]);
                return $responseBody;
            }

            Log::error('Paystack: Payment verification failed', [
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $responseBody
            ]);

            throw new \Exception('Payment verification failed: ' . json_encode($responseBody));

        } catch (\Exception $e) {
            Log::error('Paystack: Exception during verification', [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            $responseBody = $response->json();

            if ($response->successful()) {
                return $responseBody;
            }

            throw new \Exception('Failed to fetch transaction: ' . json_encode($responseBody));

        } catch (\Exception $e) {
            Log::error('Paystack: Failed to get transaction', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
     * Get secret key (for internal use only)
     */
    public function getSecretKey()
    {
        return $this->secretKey;
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
