<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    /**
     * Initialize payment
     * POST /api/payment/initialize
     */
    public function initializePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::findOrFail($request->order_id);

            // Check if order belongs to authenticated user
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to order'
                ], 403);
            }

            // Check if order is already paid
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order already paid'
                ], 400);
            }

            // Prepare metadata for Paystack
            $metadata = [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'customer_name' => auth()->user()->name ?? $request->email,
                'custom_fields' => [
                    [
                        'display_name' => 'Order ID',
                        'variable_name' => 'order_id',
                        'value' => $order->id
                    ],
                    [
                        'display_name' => 'Customer',
                        'variable_name' => 'customer_name',
                        'value' => auth()->user()->name ?? $request->email
                    ]
                ]
            ];

            // Initialize payment with Paystack
            $response = $this->paystackService->initializePayment(
                $request->email,
                $order->total_amount,
                null, // Let service generate reference
                $metadata
            );

            // Save transaction record
            $transaction = Transaction::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'reference' => $response['data']['reference'],
                'amount' => $order->total_amount,
                'status' => 'pending',
                'payment_method' => 'paystack',
            ]);

            Log::info('Payment initialized', [
                'order_id' => $order->id,
                'reference' => $transaction->reference,
                'amount' => $order->total_amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'authorization_url' => $response['data']['authorization_url'],
                    'access_code' => $response['data']['access_code'],
                    'reference' => $response['data']['reference'],
                    'amount' => $order->total_amount,
                    'order_id' => $order->id
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'error' => $e->getMessage(),
                'order_id' => $request->order_id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Charge card directly
     * POST /api/payment/charge
     */
    public function chargeCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'card' => 'required|array',
            'card.number' => 'required|string|size:16',
            'card.cvv' => 'required|string|size:3',
            'card.expiry_month' => 'required|string|size:2',
            'card.expiry_year' => 'required|string|size:4',
            'card.pin' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find existing transaction
            $transaction = Transaction::where('reference', $request->reference)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Check if already processed
            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction already processed'
                ], 400);
            }

            // Prepare card data for Paystack (FIX: Include reference)
            $cardData = [
                'email' => $request->email,
                'amount' => $request->amount, // Service will *100
                'reference' => $request->reference, // Added this line
                'card' => [
                    'number' => $request->card['number'],
                    'cvv' => $request->card['cvv'],
                    'expiry_month' => $request->card['expiry_month'],
                    'expiry_year' => substr($request->card['expiry_year'], -2), // Last 2 digits
                    'pin' => $request->card['pin'],
                ],
                'metadata' => [
                    'order_id' => $transaction->order_id,
                    'transaction_id' => $transaction->id,
                ]
            ];

            // Charge card with Paystack
            $response = $this->paystackService->chargeCard($cardData);

            Log::info('Card charge response', [
                'reference' => $request->reference,
                'status' => $response['data']['status'] ?? 'unknown',
                'gateway_response' => $response['data']['gateway_response'] ?? null
            ]);

            // Handle different response statuses
            $status = $response['data']['status'] ?? 'failed';

            if ($status === 'success') {
                // Update transaction immediately (full verification in webhook/verify)
                $transaction->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'payment_data' => $response['data']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Card charged successfully',
                    'data' => $response['data']
                ], 200);
            } elseif ($status === 'send_otp') {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP required',
                    'data' => [
                        'status' => 'send_otp',
                        'reference' => $request->reference
                    ]
                ], 200);
            } elseif ($status === 'send_pin') {
                return response()->json([
                    'success' => false,
                    'message' => 'PIN required',
                    'data' => [
                        'status' => 'send_pin',
                        'reference' => $request->reference
                    ]
                ], 200);
            } elseif ($status === 'open_url') {
                return response()->json([
                    'success' => false,
                    'message' => '3D Secure required',
                    'data' => [
                        'status' => 'open_url',
                        'url' => $response['data']['redirect_url'] ?? null
                    ]
                ], 200);
            } else {
                // Update to failed
                $transaction->update([
                    'status' => 'failed',
                    'payment_data' => $response['data']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $response['data']['gateway_response'] ?? 'Charge failed',
                    'data' => $response['data']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Card charge failed', [
                'error' => $e->getMessage(),
                'reference' => $request->reference ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Card charge failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Submit OTP
     * POST /api/payment/submit-otp
     */
    public function submitOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'otp' => 'required|string|size:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaction = Transaction::where('reference', $request->reference)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction state'
                ], 400);
            }

            // Submit OTP to Paystack
            $response = $this->paystackService->submitOtp(
                $request->reference,
                $request->otp
            );

            if ($response['data']['status'] === 'success') {
                $transaction->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'payment_data' => $response['data']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'OTP verified successfully',
                    'data' => $response['data']
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['data']['gateway_response'] ?? 'OTP verification failed',
                    'data' => $response['data']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('OTP submission failed', [
                'error' => $e->getMessage(),
                'reference' => $request->reference ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'OTP submission failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Submit PIN
     * POST /api/payment/submit-pin
     */
    public function submitPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'pin' => 'required|string|size:4'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaction = Transaction::where('reference', $request->reference)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction state'
                ], 400);
            }

            // Submit PIN to Paystack
            $response = $this->paystackService->submitPin(
                $request->reference,
                $request->pin
            );

            if ($response['data']['status'] === 'success') {
                $transaction->update([
                    'status' => 'success',
                    'paid_at' => now(),
                    'payment_data' => $response['data']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'PIN verified successfully',
                    'data' => $response['data']
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $response['data']['gateway_response'] ?? 'PIN verification failed',
                    'data' => $response['data']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('PIN submission failed', [
                'error' => $e->getMessage(),
                'reference' => $request->reference ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PIN submission failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Verify payment
     * POST /api/payment/verify
     */
    public function verifyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verify payment with Paystack
            $response = $this->paystackService->verifyPayment($request->reference);

            Log::info('Payment verification response', [
                'reference' => $request->reference,
                'status' => $response['data']['status'] ?? 'unknown'
            ]);

            if ($response['data']['status'] === 'success') {
                DB::beginTransaction();

                try {
                    // Find transaction
                    $transaction = Transaction::where('reference', $request->reference)->first();

                    if (!$transaction) {
                        throw new \Exception('Transaction not found');
                    }

                    // Check if transaction belongs to authenticated user
                    if ($transaction->user_id !== auth()->id()) {
                        throw new \Exception('Unauthorized access to transaction');
                    }

                    // Check if already processed
                    if ($transaction->status === 'success') {
                        DB::commit();
                        return response()->json([
                            'success' => true,
                            'message' => 'Payment already verified',
                            'data' => [
                                'transaction' => $transaction,
                                'order' => $transaction->order
                            ]
                        ], 200);
                    }

                    // Update transaction
                    $transaction->update([
                        'status' => 'success',
                        'paid_at' => now(),
                        'payment_data' => $response['data']
                    ]);

                    // Update order
                    $order = Order::find($transaction->order_id);
                    $order->update([
                        'payment_status' => 'paid',
                        'payment_method' => 'paystack',
                        'paid_at' => now(),
                        'status' => 'processing' // Move order to processing
                    ]);

                    DB::commit();

                    Log::info('Payment verified successfully', [
                        'reference' => $request->reference,
                        'order_id' => $order->id,
                        'amount' => $transaction->amount
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'data' => [
                            'transaction' => $transaction->fresh(),
                            'order' => $order->fresh()
                        ]
                    ], 200);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'status' => $response['data']['status'] ?? 'failed'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment verification error', [
                'error' => $e->getMessage(),
                'reference' => $request->reference ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }

    /**
     * Paystack webhook handler
     * POST /api/payment/webhook
     */
    public function webhook(Request $request)
    {
        // Verify webhook signature
        $signature = $request->header('x-paystack-signature');
        
        if (!$signature) {
            Log::warning('Webhook received without signature');
            return response()->json(['message' => 'No signature provided'], 401);
        }

        $body = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $body, config('services.paystack.secret_key'));
        
        if ($signature !== $expectedSignature) {
            Log::warning('Webhook signature verification failed', [
                'received_signature' => $signature,
                'expected_signature' => $expectedSignature
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->all();

        Log::info('Paystack webhook received', [
            'event' => $event['event'] ?? 'unknown',
            'reference' => $event['data']['reference'] ?? null
        ]);

        try {
            // Handle different event types
            if ($event['event'] === 'charge.success') {
                $this->handleChargeSuccess($event['data']);
            } elseif ($event['event'] === 'charge.failed') {
                $this->handleChargeFailed($event['data']);
            }

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'event' => $event['event'] ?? 'unknown'
            ]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle successful charge
     */
    protected function handleChargeSuccess($data)
    {
        DB::beginTransaction();

        try {
            $transaction = Transaction::where('reference', $data['reference'])->first();

            if (!$transaction) {
                Log::warning('Webhook: Transaction not found', ['reference' => $data['reference']]);
                DB::rollBack();
                return;
            }

            // Skip if already processed
            if ($transaction->status === 'success') {
                Log::info('Webhook: Transaction already processed', ['reference' => $data['reference']]);
                DB::commit();
                return;
            }

            // Update transaction
            $transaction->update([
                'status' => 'success',
                'paid_at' => now(),
                'payment_data' => $data
            ]);

            // Update order
            $order = Order::find($transaction->order_id);
            if ($order) {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_method' => 'paystack',
                    'paid_at' => now(),
                    'status' => 'processing'
                ]);
            }

            DB::commit();

            Log::info('Webhook: Payment processed successfully', [
                'reference' => $data['reference'],
                'order_id' => $order->id ?? null
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook: Failed to process charge success', [
                'error' => $e->getMessage(),
                'reference' => $data['reference'] ?? null
            ]);
            throw $e;
        }
    }

    /**
     * Handle failed charge
     */
    protected function handleChargeFailed($data)
    {
        try {
            $transaction = Transaction::where('reference', $data['reference'])->first();

            if ($transaction) {
                $transaction->update([
                    'status' => 'failed',
                    'payment_data' => $data
                ]);

                Log::info('Webhook: Payment failed', [
                    'reference' => $data['reference'],
                    'reason' => $data['gateway_response'] ?? 'Unknown'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Webhook: Failed to process charge failure', [
                'error' => $e->getMessage(),
                'reference' => $data['reference'] ?? null
            ]);
        }
    }

    /**
     * Get Paystack public key for frontend
     * GET /api/payment/public-key
     */
    public function getPublicKey()
    {
        return response()->json([
            'success' => true,
            'public_key' => $this->paystackService->getPublicKey()
        ], 200);
    }

    /**
     * Get payment history for authenticated user
     * GET /api/payment/history
     */
    public function getPaymentHistory()
    {
        try {
            $transactions = Transaction::where('user_id', auth()->id())
                ->with('order')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch payment history', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history'
            ], 500);
        }
    }
}