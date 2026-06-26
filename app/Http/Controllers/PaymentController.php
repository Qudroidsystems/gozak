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
        $this->paystackService     = $paystackService;
        $this->notificationService = $notificationService;
        $this->barcodeService      = $barcodeService;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INITIALIZE PAYMENT
    // ─────────────────────────────────────────────────────────────────────────
    public function initializePayment(Request $request)
    {
        Log::info('PaymentController@initializePayment: REQUEST RECEIVED', [
            'timestamp'              => now()->toIso8601String(),
            'request_order_id'       => $request->order_id,
            'request_email'          => $request->email,
            'auth_check'             => auth()->check(),
            'auth_id'                => auth()->id(),
            'auth_user_email'        => auth()->check() ? auth()->user()->email : 'UNAUTHENTICATED',
            'authorization_header'   => $request->header('Authorization')
                                            ? substr($request->header('Authorization'), 0, 30) . '...'
                                            : 'MISSING',
            'bearer_token_from_auth' => $request->bearerToken()
                                            ? substr($request->bearerToken(), 0, 20) . '...'
                                            : 'NULL',
        ]);

        if (! auth()->check()) {
            Log::warning('PaymentController@initializePayment: UNAUTHENTICATED', [
                'order_id'             => $request->order_id,
                'authorization_header' => $request->header('Authorization') ?? 'ABSENT',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in again.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'email'    => 'required|email',
        ]);

        if ($validator->fails()) {
            Log::warning('PaymentController@initializePayment: VALIDATION FAILED', [
                'errors'   => $validator->errors()->toArray(),
                'order_id' => $request->order_id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $order = Order::with(['user', 'items.product'])->findOrFail($request->order_id);

            Log::info('PaymentController@initializePayment: OWNERSHIP CHECK', [
                'order_id'           => $order->id,
                'order_user_id'      => $order->user_id,
                'order_user_id_type' => gettype($order->user_id),
                'auth_id'            => auth()->id(),
                'auth_id_type'       => gettype(auth()->id()),
                'strict_match'       => ($order->user_id === auth()->id()),
                'loose_match'        => ($order->user_id == auth()->id()),
                'string_match'       => ((string) $order->user_id === (string) auth()->id()),
            ]);

            // ── FIX: cast both sides to string so "12" === 12 passes ─────────
            if ((string) $order->user_id !== (string) auth()->id()) {
                Log::error('PaymentController@initializePayment: OWNERSHIP MISMATCH — returning 403', [
                    'order_user_id' => $order->user_id,
                    'auth_id'       => auth()->id(),
                    'order_id'      => $order->id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to order',
                ], 403);
            }

            if ($order->isPaid()) {
                Log::info('PaymentController@initializePayment: ORDER ALREADY PAID', [
                    'order_id'       => $order->id,
                    'payment_status' => $order->payment_status,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Order already paid',
                ], 400);
            }

            $metadata = [
                'order_id'      => $order->id,
                'user_id'       => auth()->id(),
                'customer_name' => auth()->user()->full_name
                                    ?? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name),
                'order_items'   => $order->items->map(function ($item) {
                    return [
                        'product'  => $item->title,
                        'quantity' => $item->quantity,
                        'price'    => $item->price,
                    ];
                })->toArray(),
                'custom_fields' => [
                    [
                        'display_name'  => 'Order ID',
                        'variable_name' => 'order_id',
                        'value'         => $order->id,
                    ],
                    [
                        'display_name'  => 'Customer',
                        'variable_name' => 'customer_name',
                        'value'         => auth()->user()->full_name ?? auth()->user()->email,
                    ],
                ],
            ];

            Log::info('PaymentController@initializePayment: CALLING PAYSTACK', [
                'email'    => $request->email,
                'amount'   => $order->total_amount,
                'order_id' => $order->id,
            ]);

            $response = $this->paystackService->initializePayment(
                $request->email,
                $order->total_amount,
                null,
                $metadata
            );

            Log::info('PaymentController@initializePayment: PAYSTACK RESPONSE', [
                'response_status'       => $response['status'] ?? 'unknown',
                'has_authorization_url' => isset($response['data']['authorization_url']),
                'has_reference'         => isset($response['data']['reference']),
            ]);

            $transaction = Transaction::create([
                'order_id'       => $order->id,
                'user_id'        => auth()->id(),
                'reference'      => $response['data']['reference'],
                'amount'         => $order->total_amount,
                'status'         => 'pending',
                'payment_method' => 'paystack',
            ]);

            Log::info('PaymentController@initializePayment: SUCCESS', [
                'order_id'          => $order->id,
                'transaction_id'    => $transaction->id,
                'reference'         => $transaction->reference,
                'amount'            => $order->total_amount,
                'authorization_url' => $response['data']['authorization_url'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data'    => [
                    'authorization_url' => $response['data']['authorization_url'],
                    'access_code'       => $response['data']['access_code'],
                    'reference'         => $response['data']['reference'],
                    'amount'            => $order->total_amount,
                    'order_id'          => $order->id,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('PaymentController@initializePayment: EXCEPTION', [
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
                'order_id' => $request->order_id ?? null,
                'auth_id'  => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VERIFY PAYMENT
    // ─────────────────────────────────────────────────────────────────────────
    public function verifyPayment(Request $request)
    {
        Log::info('PaymentController@verifyPayment: REQUEST RECEIVED', [
            'reference'            => $request->reference,
            'auth_check'           => auth()->check(),
            'auth_id'              => auth()->id(),
            'authorization_header' => $request->header('Authorization')
                                        ? substr($request->header('Authorization'), 0, 30) . '...'
                                        : 'MISSING',
        ]);

        if (! auth()->check()) {
            Log::warning('PaymentController@verifyPayment: UNAUTHENTICATED', [
                'reference' => $request->reference,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in again.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->paystackService->verifyPayment($request->reference);

            Log::info('PaymentController@verifyPayment: PAYSTACK RESPONSE', [
                'reference'       => $request->reference,
                'paystack_status' => $response['data']['status'] ?? 'unknown',
            ]);

            if ($response['data']['status'] === 'success') {
                return DB::transaction(function () use ($request, $response) {
                    $transaction = Transaction::where('reference', $request->reference)->first();

                    if (! $transaction) {
                        Log::error('PaymentController@verifyPayment: TRANSACTION NOT FOUND', [
                            'reference' => $request->reference,
                        ]);
                        throw new \Exception('Transaction not found');
                    }

                    Log::info('PaymentController@verifyPayment: TRANSACTION OWNERSHIP CHECK', [
                        'transaction_user_id'      => $transaction->user_id,
                        'transaction_user_id_type' => gettype($transaction->user_id),
                        'auth_id'                  => auth()->id(),
                        'auth_id_type'             => gettype(auth()->id()),
                        'strict_match'             => ($transaction->user_id === auth()->id()),
                        'string_match'             => ((string) $transaction->user_id === (string) auth()->id()),
                    ]);

                    // ── FIX: same string-cast comparison ─────────────────────
                    if ((string) $transaction->user_id !== (string) auth()->id()) {
                        Log::error('PaymentController@verifyPayment: OWNERSHIP MISMATCH', [
                            'transaction_user_id' => $transaction->user_id,
                            'auth_id'             => auth()->id(),
                        ]);
                        throw new \Exception('Unauthorized access to transaction');
                    }

                    if ($transaction->status === 'success') {
                        Log::info('PaymentController@verifyPayment: ALREADY VERIFIED', [
                            'reference' => $request->reference,
                        ]);
                        $order = Order::with(['items.product', 'shippingAddress', 'billingAddress'])
                            ->find($transaction->order_id);
                        return response()->json([
                            'success' => true,
                            'message' => 'Payment already verified',
                            'data'    => ['transaction' => $transaction, 'order' => $order],
                        ], 200);
                    }

                    $transaction->update([
                        'status'       => 'success',
                        'paid_at'      => now(),
                        'payment_data' => $response['data'],
                    ]);

                    $order                 = Order::find($transaction->order_id);
                    $order->payment_status = 'paid';
                    $order->paid_at        = now();
                    $order->status         = 'processing';
                    $order->save();

                    Log::info('PaymentController@verifyPayment: ORDER UPDATED', [
                        'order_id'       => $order->id,
                        'payment_status' => $order->payment_status,
                        'status'         => $order->status,
                    ]);

                    $barcodeResult      = $this->barcodeService->generateBarcodeForOrder($order);
                    $notificationResult = $this->notificationService->sendOrderConfirmation($order);

                    Log::info('PaymentController@verifyPayment: SUCCESS', [
                        'reference'         => $request->reference,
                        'order_id'          => $order->id,
                        'barcode_generated' => $barcodeResult['success'],
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment verified successfully',
                        'data'    => [
                            'transaction'   => $transaction->fresh(),
                            'order'         => $order->fresh(['items.product', 'shippingAddress', 'billingAddress']),
                            'barcode_url'   => $barcodeResult['barcode_url'] ?? null,
                            'notifications' => $notificationResult,
                        ],
                    ], 200);
                });
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'status'  => $response['data']['status'] ?? 'failed',
            ], 400);

        } catch (\Exception $e) {
            Log::error('PaymentController@verifyPayment: EXCEPTION', [
                'error'     => $e->getMessage(),
                'reference' => $request->reference ?? null,
                'auth_id'   => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'error'   => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CHARGE CARD
    // ─────────────────────────────────────────────────────────────────────────
    public function chargeCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference'         => 'required|string',
            'email'             => 'required|email',
            'amount'            => 'required|numeric|min:0',
            'card'              => 'required|array',
            'card.number'       => 'required|string|min:13|max:19',
            'card.cvv'          => 'required|string|min:3|max:4',
            'card.expiry_month' => 'required|string|size:2',
            'card.expiry_year'  => 'required|string',
            'card.pin'          => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $transaction = Transaction::where('reference', $request->reference)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction already processed',
                ], 400);
            }

            $expiryYear    = $request->card['expiry_year'];
            $formattedYear = strlen($expiryYear) === 4 ? substr($expiryYear, -2) : $expiryYear;

            $cardData = [
                'email'     => $request->email,
                'amount'    => $request->amount,
                'reference' => $request->reference,
                'card'      => [
                    'number'       => $request->card['number'],
                    'cvv'          => $request->card['cvv'],
                    'expiry_month' => $request->card['expiry_month'],
                    'expiry_year'  => $formattedYear,
                    'pin'          => $request->card['pin'],
                ],
                'metadata' => [
                    'order_id'       => $transaction->order_id,
                    'transaction_id' => $transaction->id,
                ],
            ];

            Log::info('PaymentController@chargeCard: Charging card', [
                'reference' => $request->reference,
                'amount'    => $request->amount,
                'email'     => $request->email,
            ]);

            $response = $this->paystackService->chargeCard($cardData);

            if ($response['status'] === true && isset($response['data'])) {
                $status = $response['data']['status'];

                if ($status === 'success') {
                    return $this->handleSuccessfulPayment($transaction, $response);
                } elseif ($status === 'send_otp') {
                    return response()->json([
                        'success' => true,
                        'message' => 'OTP required',
                        'data'    => ['status' => 'send_otp', 'reference' => $request->reference],
                    ], 200);
                } elseif ($status === 'send_pin') {
                    return response()->json([
                        'success' => true,
                        'message' => 'PIN required',
                        'data'    => ['status' => 'send_pin', 'reference' => $request->reference],
                    ], 200);
                } elseif (in_array($status, ['open_url', 'pending'])) {
                    return response()->json([
                        'success' => true,
                        'message' => '3D Secure authentication required',
                        'data'    => [
                            'status'    => $status,
                            'url'       => $response['data']['url'] ?? null,
                            'reference' => $request->reference,
                        ],
                    ], 200);
                } else {
                    return $this->handleFailedPayment($transaction, $response);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid response from payment gateway',
                'error'   => $response['message'] ?? 'Unknown error',
            ], 500);

        } catch (\Exception $e) {
            Log::error('PaymentController@chargeCard: EXCEPTION', [
                'error'     => $e->getMessage(),
                'reference' => $request->reference ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Card charge failed',
                'error'   => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED SUCCESS / FAILURE HANDLERS
    // ─────────────────────────────────────────────────────────────────────────
    protected function handleSuccessfulPayment(Transaction $transaction, array $response)
    {
        return DB::transaction(function () use ($transaction, $response) {
            $transaction->update([
                'status'       => 'success',
                'paid_at'      => now(),
                'payment_data' => $response['data'],
            ]);

            $order                 = Order::with('user')->find($transaction->order_id);
            $order->payment_status = 'paid';
            $order->paid_at        = now();
            $order->status         = 'processing';
            $order->save();

            Log::info('PaymentController@handleSuccessfulPayment: ORDER UPDATED', [
                'order_id'       => $order->id,
                'payment_status' => $order->payment_status,
                'status'         => $order->status,
                'reference'      => $transaction->reference,
            ]);

            $barcodeResult      = $this->barcodeService->generateBarcodeForOrder($order);
            $notificationResult = $this->notificationService->sendOrderConfirmation($order);

            return response()->json([
                'success' => true,
                'message' => 'Payment successful! Order confirmed.',
                'data'    => [
                    'status'        => 'success',
                    'reference'     => $transaction->reference,
                    'order'         => $order->fresh(['items.product', 'shippingAddress', 'billingAddress']),
                    'barcode_url'   => $barcodeResult['barcode_url'] ?? null,
                    'notifications' => $notificationResult,
                ],
            ], 200);
        });
    }

    protected function handleFailedPayment(Transaction $transaction, array $response)
    {
        $transaction->update([
            'status'       => 'failed',
            'payment_data' => $response['data'],
        ]);

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Charge failed',
            'data'    => [
                'status'           => $response['data']['status'],
                'gateway_response' => $response['data']['gateway_response'] ?? null,
            ],
        ], 400);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OTP / PIN
    // ─────────────────────────────────────────────────────────────────────────
    public function submitOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'otp'       => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $transaction = Transaction::where('reference', $request->reference)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction state',
                ], 400);
            }

            $response = $this->paystackService->submitOtp($request->reference, $request->otp);

            return $response['data']['status'] === 'success'
                ? $this->handleSuccessfulPayment($transaction, $response)
                : $this->handleFailedPayment($transaction, $response);

        } catch (\Exception $e) {
            Log::error('PaymentController@submitOtp: EXCEPTION', [
                'error'     => $e->getMessage(),
                'reference' => $request->reference ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'OTP submission failed',
                'error'   => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    public function submitPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'pin'       => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $transaction = Transaction::where('reference', $request->reference)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            if ($transaction->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction state',
                ], 400);
            }

            $response = $this->paystackService->submitPin($request->reference, $request->pin);

            return $response['data']['status'] === 'success'
                ? $this->handleSuccessfulPayment($transaction, $response)
                : $this->handleFailedPayment($transaction, $response);

        } catch (\Exception $e) {
            Log::error('PaymentController@submitPin: EXCEPTION', [
                'error'     => $e->getMessage(),
                'reference' => $request->reference ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'PIN submission failed',
                'error'   => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEBHOOK
    // ─────────────────────────────────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature');

        if (! $signature) {
            Log::warning('PaymentController@webhook: No signature provided');
            return response()->json(['message' => 'No signature provided'], 401);
        }

        $body              = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $body, config('services.paystack.secret_key'));

        if ($signature !== $expectedSignature) {
            Log::warning('PaymentController@webhook: SIGNATURE MISMATCH');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->all();

        Log::info('PaymentController@webhook: EVENT RECEIVED', [
            'event'     => $event['event'] ?? 'unknown',
            'reference' => $event['data']['reference'] ?? null,
        ]);

        try {
            if ($event['event'] === 'charge.success') {
                $this->handleChargeSuccess($event['data']);
            } elseif ($event['event'] === 'charge.failed') {
                $this->handleChargeFailed($event['data']);
            }

            return response()->json(['message' => 'Webhook processed successfully'], 200);
        } catch (\Exception $e) {
            Log::error('PaymentController@webhook: EXCEPTION', [
                'error' => $e->getMessage(),
                'event' => $event['event'] ?? 'unknown',
            ]);
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    protected function handleChargeSuccess($data)
    {
        DB::beginTransaction();
        try {
            $transaction = Transaction::where('reference', $data['reference'])->first();

            if (! $transaction) {
                Log::warning('PaymentController@handleChargeSuccess: TRANSACTION NOT FOUND', [
                    'reference' => $data['reference'],
                ]);
                DB::rollBack();
                return;
            }

            if ($transaction->status === 'success') {
                Log::info('PaymentController@handleChargeSuccess: ALREADY PROCESSED', [
                    'reference' => $data['reference'],
                ]);
                DB::commit();
                return;
            }

            $transaction->update([
                'status'       => 'success',
                'paid_at'      => now(),
                'payment_data' => $data,
            ]);

            $order = Order::with('user')->find($transaction->order_id);
            if ($order) {
                $order->payment_status = 'paid';
                $order->paid_at        = now();
                $order->status         = 'processing';
                $order->save();

                Log::info('PaymentController@handleChargeSuccess: ORDER UPDATED', [
                    'order_id'       => $order->id,
                    'payment_status' => $order->payment_status,
                    'was_changed'    => $order->wasChanged(),
                ]);

                $this->barcodeService->generateBarcodeForOrder($order);
                $this->notificationService->sendOrderConfirmation($order);
            }

            DB::commit();

            Log::info('PaymentController@handleChargeSuccess: SUCCESS', [
                'reference' => $data['reference'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PaymentController@handleChargeSuccess: EXCEPTION', [
                'error'     => $e->getMessage(),
                'reference' => $data['reference'] ?? null,
            ]);
        }
    }

    protected function handleChargeFailed($data)
    {
        try {
            $transaction = Transaction::where('reference', $data['reference'])->first();

            if ($transaction) {
                $transaction->update([
                    'status'       => 'failed',
                    'payment_data' => $data,
                ]);
                Log::info('PaymentController@handleChargeFailed: TRANSACTION MARKED FAILED', [
                    'reference' => $data['reference'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PaymentController@handleChargeFailed: EXCEPTION', [
                'error'     => $e->getMessage(),
                'reference' => $data['reference'] ?? null,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MISC
    // ─────────────────────────────────────────────────────────────────────────
    public function getPublicKey()
    {
        return response()->json([
            'success'    => true,
            'public_key' => $this->paystackService->getPublicKey(),
        ], 200);
    }

    public function getPaymentHistory()
    {
        try {
            $transactions = Transaction::where('user_id', auth()->id())
                ->with(['order.items.product', 'order.shippingAddress'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $transactions], 200);
        } catch (\Exception $e) {
            Log::error('PaymentController@getPaymentHistory: EXCEPTION', [
                'error'   => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history',
            ], 500);
        }
    }

    public function successPage(Request $request)
    {
        return view('payment.success', [
            'reference' => $request->query('reference'),
        ]);
    }
}
