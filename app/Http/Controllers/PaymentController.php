<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentGateway;
use App\Services\Payment\OpayGateway;
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
        NotificationService $notificationService,
        BarcodeService $barcodeService
    ) {
        $this->notificationService = $notificationService;
        $this->barcodeService      = $barcodeService;
    }

    /**
     * Paystack is built only when needed, so OPay keeps working even when
     * Paystack has no keys (PaystackService throws without a secret key).
     */
    protected function paystack(): PaystackService
    {
        return $this->paystackService ??= app(PaystackService::class);
    }

    /** Gateways that are switched on and have keys for their current mode. */
    protected function readyGateways(): array
    {
        try {
            $rows = PaymentGateway::active()->whereIn('provider_key', ['paystack', 'opay'])->get()
                ->filter(fn ($g) => $g->isConfigured());
        } catch (\Throwable $e) {
            $rows = collect(); // table not migrated yet
        }

        if ($rows->isEmpty() && config('services.paystack.secret_key')) {
            // Before the payment_gateways migration: behave exactly as before.
            return ['paystack' => ['key' => 'paystack', 'name' => 'Paystack', 'mode' => 'env']];
        }

        $order = ['paystack' => 1, 'opay' => 2];
        return $rows->sortBy(fn ($g) => $order[$g->provider_key] ?? 9)
            ->mapWithKeys(fn ($g) => [$g->provider_key => [
                'key'  => $g->provider_key,
                'name' => $g->name,
                'mode' => $g->mode,
            ]])->all();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GATEWAYS (for the app's checkout screen)
    // ─────────────────────────────────────────────────────────────────────────
    public function gateways()
    {
        $ready = $this->readyGateways();
        $meta  = [
            'paystack' => ['label' => 'Pay with Card / Bank (Paystack)', 'icon' => 'credit_card'],
            'opay'     => ['label' => 'Pay with OPay',                   'icon' => 'account_balance_wallet'],
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'default'  => array_key_first($ready),
                'gateways' => array_values(array_map(fn ($g) => $g + ($meta[$g['key']] ?? []), $ready)),
            ],
        ]);
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
            'gateway'  => 'nullable|in:paystack,opay',
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

            // Which gateway? The app may ask for one; otherwise use the first one switched on.
            $ready   = $this->readyGateways();
            $gateway = $request->input('gateway') ?: array_key_first($ready);

            if (!$gateway || !isset($ready[$gateway])) {
                return response()->json([
                    'success' => false,
                    'message' => $gateway
                        ? ucfirst($gateway) . ' payments are not available right now. Please choose another payment method.'
                        : 'Online payment is not available right now.',
                    'available_gateways' => array_keys($ready),
                ], 422);
            }

            Log::info('PaymentController@initializePayment: CALLING GATEWAY', [
                'gateway'  => $gateway,
                'email'    => $request->email,
                'amount'   => $order->total_amount,
                'order_id' => $order->id,
            ]);

            if ($gateway === 'opay') {
                $metadata['phone'] = auth()->user()->phone_number ?? null;
                $response = app(OpayGateway::class)->initialize(
                    $request->email,
                    (float) $order->total_amount,
                    OpayGateway::generateReference(),
                    $metadata
                );
            } else {
                $response = $this->paystack()->initializePayment(
                    $request->email,
                    $order->total_amount,
                    null,
                    $metadata
                );
            }

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
                'payment_method' => $gateway,
            ]);

            // Keep the order in sync with the gateway actually used (the customer may switch at the payment step).
            if ($order->payment_method !== $gateway) {
                $order->forceFill(['payment_method' => $gateway])->save();
            }

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
                    'gateway'           => $gateway,
                    // The app's WebView closes when it reaches one of these URLs, then calls /payment/verify.
                    'callback_url'      => $gateway === 'opay'
                        ? route('payment.opay.return')
                        : (config('services.paystack.callback_url') ?: route('payment.callback')),
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
            $response = $this->verifyWithGateway($request->reference);

            Log::info('PaymentController@verifyPayment: GATEWAY RESPONSE', [
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

            $response = $this->paystack()->chargeCard($cardData);

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

            $response = $this->paystack()->submitOtp($request->reference, $request->otp);

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

            $response = $this->paystack()->submitPin($request->reference, $request->pin);

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
        if (! $this->paystack()->verifyWebhookSignature($signature, $body)) {
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
        try {
            $key = $this->paystack()->getPublicKey();
        } catch (\Exception $e) {
            $key = null;
        }
        return response()->json([
            'success'    => (bool) $key,
            'public_key' => $key,
        ], $key ? 200 : 503);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GATEWAY DISPATCH
    // ─────────────────────────────────────────────────────────────────────────

    /** Verify with whichever gateway the transaction was started on. Paystack-shaped result. */
    protected function verifyWithGateway(string $reference): array
    {
        $method = Transaction::where('reference', $reference)->value('payment_method');

        if ($method === 'opay' || ($method === null && str_starts_with($reference, 'OPAY_'))) {
            return app(OpayGateway::class)->verify($reference);
        }
        return $this->paystack()->verifyPayment($reference);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OPAY CALLBACK (server-to-server) — POST /api/payment/opay/webhook
    // ─────────────────────────────────────────────────────────────────────────
    public function opayWebhook(Request $request)
    {
        $body      = $request->all();
        $reference = (string) ($body['payload']['reference'] ?? $request->input('reference', ''));

        Log::info('PaymentController@opayWebhook: CALLBACK RECEIVED', [
            'reference' => $reference,
            'status'    => $body['payload']['status'] ?? null,
        ]);

        if ($reference === '') {
            return response()->json(['message' => 'No reference'], 400);
        }

        $opay = app(OpayGateway::class);
        if (! $opay->validCallback($body)) {
            // Not fatal: we never trust the callback body, we re-query OPay below.
            Log::warning('PaymentController@opayWebhook: signature did not match, re-querying OPay', ['reference' => $reference]);
        }

        try {
            $result = $opay->verify($reference);
            $status = $result['data']['status'] ?? 'pending';

            if ($status === 'success') {
                $this->handleChargeSuccess($result['data']);
            } elseif (in_array($status, ['failed', 'abandoned'], true)) {
                $this->handleChargeFailed($result['data']);
            }
            return response()->json(['message' => 'Callback processed'], 200);
        } catch (\Exception $e) {
            Log::error('PaymentController@opayWebhook: EXCEPTION', ['reference' => $reference, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Callback processing failed'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OPAY RETURN PAGE — where OPay sends the customer's browser/WebView back to
    // ─────────────────────────────────────────────────────────────────────────
    public function opayReturn(Request $request)
    {
        $reference = (string) $request->query('reference', '');
        $status    = 'pending';

        if ($reference !== '') {
            try {
                $result = app(OpayGateway::class)->verify($reference);
                $status = $result['data']['status'] ?? 'pending';
                if ($status === 'success') {
                    $this->handleChargeSuccess($result['data']); // safe to repeat
                } elseif (in_array($status, ['failed', 'abandoned'], true)) {
                    $this->handleChargeFailed($result['data']);
                }
            } catch (\Exception $e) {
                Log::warning('PaymentController@opayReturn: verify failed', ['reference' => $reference, 'error' => $e->getMessage()]);
            }
        }

        return view('payment-callback', [
            'reference' => $reference,
            'gateway'   => 'opay',
            'status'    => $status,
        ]);
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

    /** GET /api/payment/{reference} — one of the signed-in user's transactions. */
    public function getPayment(string $reference)
    {
        $transaction = Transaction::where('reference', $reference)
            ->where('user_id', auth()->id())
            ->with(['order.items.product'])
            ->first();

        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $transaction], 200);
    }

    public function successPage(Request $request)
    {
        return view('payment.success', [
            'reference' => $request->query('reference'),
        ]);
    }
}
