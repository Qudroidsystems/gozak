<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\BarcodeService;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paystackService;
    protected $notificationService;
    protected $barcodeService;

    public function __construct(
        PaystackService $paystackService,
        NotificationService $notificationService,
        BarcodeService $barcodeService
    ) {
        $this->paystackService = $paystackService;
        $this->notificationService = $notificationService;
        $this->barcodeService = $barcodeService;
    }

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
        $order = Order::with(['user', 'items.product'])->findOrFail($request->order_id);

        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to order'
            ], 403);
        }

        if ($order->isPaid()) {
            return response()->json([
                'success' => false,
                'message' => 'Order already paid'
            ], 400);
        }

        // Debug log for amount - ADD MORE DETAILED LOGGING
        Log::info('PaymentController: Starting payment initialization', [
            'order_id' => $order->id,
            'order_amount' => $order->total_amount,
            'amount_type' => gettype($order->total_amount),
            'amount_value' => $order->total_amount,
            'user_email' => $request->email
        ]);

        $metadata = [
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'customer_name' => auth()->user()->full_name,
            'order_items' => $order->items->map(function ($item) {
                return [
                    'product' => $item->title,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            })->toArray(),
            'custom_fields' => [
                [
                    'display_name' => 'Order ID',
                    'variable_name' => 'order_id',
                    'value' => $order->id
                ],
                [
                    'display_name' => 'Customer',
                    'variable_name' => 'customer_name',
                    'value' => auth()->user()->full_name
                ]
            ]
        ];

        Log::info('PaymentController: Calling PaystackService->initializePayment', [
            'email' => $request->email,
            'amount' => $order->total_amount,
            'metadata' => $metadata
        ]);

        // Call Paystack service
        $response = $this->paystackService->initializePayment(
            $request->email,
            $order->total_amount,
            null,
            $metadata
        );

        Log::info('PaymentController: PaystackService response received', [
            'response_status' => isset($response['status']) ? $response['status'] : 'unknown',
            'has_authorization_url' => isset($response['data']['authorization_url'])
        ]);

        $transaction = Transaction::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'reference' => $response['data']['reference'],
            'amount' => $order->total_amount,
            'status' => 'pending',
            'payment_method' => 'paystack',
        ]);

        Log::info('Payment initialized successfully', [
            'order_id' => $order->id,
            'reference' => $transaction->reference,
            'amount' => $order->total_amount,
            'payment_status_before' => $order->payment_status,
            'order_status_before' => $order->status,
            'authorization_url' => $response['data']['authorization_url']
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
        Log::error('PaymentController: Payment initialization failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'order_id' => $request->order_id ?? null,
            'email' => $request->email ?? null
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Payment initialization failed',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function chargeCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'card' => 'required|array',
            'card.number' => 'required|string|min:13|max:19',
            'card.cvv' => 'required|string|min:3|max:4',
            'card.expiry_month' => 'required|string|size:2',
            'card.expiry_year' => 'required|string',
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
            $transaction = Transaction::where('reference', $request->reference)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction already processed'
                ], 400);
            }

            $expiryYear = $request->card['expiry_year'];
            $formattedYear = strlen($expiryYear) === 4 ? substr($expiryYear, -2) : $expiryYear;

            $cardData = [
                'email' => $request->email,
                'amount' => $request->amount,
                'reference' => $request->reference,
                'card' => [
                    'number' => $request->card['number'],
                    'cvv' => $request->card['cvv'],
                    'expiry_month' => $request->card['expiry_month'],
                    'expiry_year' => $formattedYear,
                    'pin' => $request->card['pin'],
                ],
                'metadata' => [
                    'order_id' => $transaction->order_id,
                    'transaction_id' => $transaction->id,
                ]
            ];

            Log::info('Attempting card charge', [
                'reference' => $request->reference,
                'amount' => $request->amount,
                'email' => $request->email
            ]);

            $response = $this->paystackService->chargeCard($cardData);

            Log::info('Card charge response received', [
                'reference' => $request->reference,
                'response_status' => $response['status'] ?? 'unknown',
                'data_status' => $response['data']['status'] ?? 'unknown'
            ]);

            if ($response['status'] === true && isset($response['data'])) {
                $status = $response['data']['status'];

                if ($status === 'success') {
                    return $this->handleSuccessfulPayment($transaction, $response);
                }
                elseif ($status === 'send_otp') {
                    return response()->json([
                        'success' => true,
                        'message' => 'OTP required',
                        'data' => [
                            'status' => 'send_otp',
                            'reference' => $request->reference
                        ]
                    ], 200);
                }
                elseif ($status === 'send_pin') {
                    return response()->json([
                        'success' => true,
                        'message' => 'PIN required',
                        'data' => [
                            'status' => 'send_pin',
                            'reference' => $request->reference
                        ]
                    ], 200);
                }
                elseif (in_array($status, ['open_url', 'pending'])) {
                    return response()->json([
                        'success' => true,
                        'message' => '3D Secure authentication required',
                        'data' => [
                            'status' => $status,
                            'url' => $response['data']['url'] ?? null,
                            'reference' => $request->reference
                        ]
                    ], 200);
                }
                else {
                    return $this->handleFailedPayment($transaction, $response);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid response from payment gateway',
                'error' => $response['message'] ?? 'Unknown error'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Card charge exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reference' => $request->reference ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Card charge failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred during payment processing'
            ], 500);
        }
    }

    protected function handleSuccessfulPayment(Transaction $transaction, array $response)
    {
        return DB::transaction(function () use ($transaction, $response) {
            $transaction->update([
                'status' => 'success',
                'paid_at' => now(),
                'payment_data' => $response['data']
            ]);

            $order = Order::with('user')->find($transaction->order_id);

            // MANUALLY UPDATE ORDER STATUS TO ENSURE IT'S SAVED
            $order->payment_status = 'paid';
            $order->paid_at = now();
            $order->status = 'processing';
            $order->save();

            Log::info('Payment successful - order status updated', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
                'order_status' => $order->status,
                'reference' => $transaction->reference
            ]);

            $barcodeResult = $this->barcodeService->generateBarcodeForOrder($order);

            $notificationResult = $this->notificationService->sendOrderConfirmation($order);

            Log::info('Payment successful with notifications', [
                'order_id' => $order->id,
                'reference' => $transaction->reference,
                'barcode_generated' => $barcodeResult['success'],
                'notifications_sent' => !isset($notificationResult['error'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment successful! Order confirmed.',
                'data' => [
                    'status' => 'success',
                    'reference' => $transaction->reference,
                    'order' => $order->fresh(['items.product', 'shippingAddress', 'billingAddress']),
                    'barcode_url' => $barcodeResult['barcode_url'] ?? null,
                    'notifications' => $notificationResult
                ]
            ], 200);
        });
    }

    protected function handleFailedPayment(Transaction $transaction, array $response)
    {
        $transaction->update([
            'status' => 'failed',
            'payment_data' => $response['data']
        ]);

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Charge failed',
            'data' => [
                'status' => $response['data']['status'],
                'gateway_response' => $response['data']['gateway_response'] ?? null
            ]
        ], 400);
    }

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

            $response = $this->paystackService->submitOtp(
                $request->reference,
                $request->otp
            );

            if ($response['data']['status'] === 'success') {
                return $this->handleSuccessfulPayment($transaction, $response);
            } else {
                return $this->handleFailedPayment($transaction, $response);
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

            $response = $this->paystackService->submitPin(
                $request->reference,
                $request->pin
            );

            if ($response['data']['status'] === 'success') {
                return $this->handleSuccessfulPayment($transaction, $response);
            } else {
                return $this->handleFailedPayment($transaction, $response);
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
            $response = $this->paystackService->verifyPayment($request->reference);

            Log::info('Payment verification response', [
                'reference' => $request->reference,
                'status' => $response['data']['status'] ?? 'unknown'
            ]);

            if ($response['data']['status'] === 'success') {
                return DB::transaction(function () use ($request, $response) {
                    $transaction = Transaction::where('reference', $request->reference)->first();

                    if (!$transaction) {
                        throw new \Exception('Transaction not found');
                    }

                    if ($transaction->user_id !== auth()->id()) {
                        throw new \Exception('Unauthorized access to transaction');
                    }

                    if ($transaction->status === 'success') {
                        $order = Order::with(['items.product', 'shippingAddress', 'billingAddress'])
                                    ->find($transaction->order_id);

                        return response()->json([
                            'success' => true,
                            'message' => 'Payment already verified',
                            'data' => [
                                'transaction' => $transaction,
                                'order' => $order
                            ]
                        ], 200);
                    }

                    $transaction->update([
                        'status' => 'success',
                        'paid_at' => now(),
                        'payment_data' => $response['data']
                    ]);

                    $order = Order::find($transaction->order_id);

                    // MANUALLY UPDATE ORDER STATUS
                    $order->payment_status = 'paid';
                    $order->paid_at = now();
                    $order->status = 'processing';
                    $order->save();

                    $barcodeResult = $this->barcodeService->generateBarcodeForOrder($order);
                    $notificationResult = $this->notificationService->sendOrderConfirmation($order);

                    Log::info('Payment verified successfully with notifications', [
                        'reference' => $request->reference,
                        'order_id' => $order->id,
                        'payment_status' => $order->payment_status,
                        'barcode_generated' => $barcodeResult['success']
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'data' => [
                            'transaction' => $transaction->fresh(),
                            'order' => $order->fresh(['items.product', 'shippingAddress', 'billingAddress']),
                            'barcode_url' => $barcodeResult['barcode_url'] ?? null,
                            'notifications' => $notificationResult
                        ]
                    ], 200);
                });
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

    public function webhook(Request $request)
    {
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
            'reference' => $event['data']['reference'] ?? null,
            'metadata' => $event['data']['metadata'] ?? null
        ]);

        try {
            if ($event['event'] === 'charge.success') {
                $this->handleChargeSuccess($event['data']);
            } elseif ($event['event'] === 'charge.failed') {
                $this->handleChargeFailed($event['data']);
            }

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event' => $event['event'] ?? 'unknown'
            ]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

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

            Log::info('Webhook: Transaction updated', [
                'transaction_id' => $transaction->id,
                'order_id' => $transaction->order_id
            ]);

            $order = Order::with('user')->find($transaction->order_id);
            if ($order) {
                // MANUALLY UPDATE ORDER STATUS - CRITICAL FIX
                $order->payment_status = 'paid';
                $order->paid_at = now();
                $order->status = 'processing';
                $order->save();

                Log::info('Webhook: Order marked as paid', [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                    'status' => $order->status,
                    'saved' => $order->wasChanged()
                ]);

                $this->barcodeService->generateBarcodeForOrder($order);
                $this->notificationService->sendOrderConfirmation($order);
            }

            DB::commit();

            Log::info('Webhook: Payment processed successfully', [
                'reference' => $data['reference'],
                'order_id' => $order->id ?? null,
                'transaction_id' => $transaction->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook: Failed to process charge success', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'reference' => $data['reference'] ?? null
            ]);
            // Don't throw - just log the error
            return;
        }
    }

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

    public function getPublicKey()
    {
        return response()->json([
            'success' => true,
            'public_key' => $this->paystackService->getPublicKey()
        ], 200);
    }

    public function getPaymentHistory()
    {
        try {
            $transactions = Transaction::where('user_id', auth()->id())
                ->with(['order.items.product', 'order.shippingAddress'])
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

    // NEW: Add success page route
    public function successPage(Request $request)
    {
        return view('payment.success', [
            'reference' => $request->query('reference')
        ]);
    }
}
