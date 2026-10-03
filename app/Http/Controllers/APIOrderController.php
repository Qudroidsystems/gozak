<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Address;
use App\Models\OrderItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\BarcodeService;
use App\Services\NotificationService;
use App\Services\OrderNotificationService;
use App\Services\OrderPricingService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderPlacedNotification;

class APIOrderController extends Controller
{
    protected $notificationService;
    protected $barcodeService;
    protected $orderNotificationService;

    public function __construct(
        NotificationService      $notificationService,
        BarcodeService           $barcodeService,
        OrderNotificationService $orderNotificationService
    ) {
        $this->notificationService      = $notificationService;
        $this->barcodeService           = $barcodeService;
        $this->orderNotificationService = $orderNotificationService;
    }

    /**
     * Create a new order and notify the user via FCM.
     */
    public function store(Request $request, OrderPricingService $pricing)
    {
        try {
            $validated = $request->validate([
                // Kept for older app builds — the server ignores these values and
                // works out user, status and every amount itself (see below).
                'user_id'                          => 'nullable',
                'status'                           => 'nullable|string',
                'total_amount'                     => 'nullable|numeric|min:0',
                'total'                            => 'nullable|numeric|min:0',
                'shipping_cost'                    => 'nullable|numeric|min:0',
                'tax_cost'                         => 'nullable|numeric|min:0',
                'order_date'                       => 'nullable|date',
                'payment_method'                   => 'required|string|max:50',
                'shipping_address'                 => 'required|array',
                'shipping_address.name'            => 'required|string|max:255',
                'shipping_address.street'          => 'required|string|max:255',
                'shipping_address.city'            => 'required|string|max:255',
                'shipping_address.state'           => 'nullable|string|max:255',
                'shipping_address.lga'             => 'nullable|string|max:255',
                'shipping_address.landmark'        => 'nullable|string|max:255',
                'shipping_address.address_type'    => 'nullable|string|max:20',
                'shipping_address.alternate_phone' => 'nullable|string|max:50',
                'shipping_address.postal_code'     => 'nullable|string|max:20',
                'shipping_address.country'         => 'required|string|max:255',
                'shipping_address.phone_number'    => 'nullable|string|max:50',
                'billing_address'                  => 'required_if:billing_address_same_as_shipping,false|array',
                'billing_address.name'             => 'required_if:billing_address_same_as_shipping,false|string|max:255',
                'billing_address.street'           => 'required_if:billing_address_same_as_shipping,false|string|max:255',
                'billing_address.city'             => 'required_if:billing_address_same_as_shipping,false|string|max:255',
                'billing_address.state'            => 'nullable|string|max:255',
                'billing_address.lga'              => 'nullable|string|max:255',
                'billing_address.landmark'         => 'nullable|string|max:255',
                'billing_address.postal_code'      => 'nullable|string|max:20',
                'billing_address.country'          => 'required_if:billing_address_same_as_shipping,false|string|max:255',
                'billing_address.phone_number'     => 'nullable|string|max:50',
                'billing_address_same_as_shipping' => 'required|boolean',
                'delivery_date'                    => 'nullable|date',
                'items'                            => 'required|array|min:1|max:100',
                'items.*.product_id'               => 'required|exists:products,id',
                'items.*.title'                    => 'nullable|string|max:255',
                'items.*.price'                    => 'nullable|numeric|min:0',
                'items.*.quantity'                 => 'required|integer|min:1|max:1000',
                'items.*.variation_id'             => 'nullable|exists:product_variations,id',
                'items.*.image'                    => 'nullable|string|max:2048',
                'items.*.brand_name'               => 'nullable|string|max:255',
                'items.*.selected_variation'       => 'nullable|array',
            ]);

            if ($validated['billing_address_same_as_shipping']) {
                $validated['billing_address'] = $validated['shipping_address'];
            }

            // ── Price everything from the database ──────────────────────────
            // The app's totals are never trusted: a modified app could otherwise
            // create a ₦1 order for expensive items and pay ₦1.
            $quote        = $pricing->price($validated['items']);
            $clientTotal  = isset($validated['total_amount']) ? (float) $validated['total_amount'] : null;
            $priceChanged = $quote['changed']
                || ($clientTotal !== null && abs($clientTotal - $quote['total']) >= 0.01);

            if ($quote['total'] <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'These items cannot be ordered right now (no price set). Please contact support.',
                ], 422);
            }

            $userId = Auth::id();

            return DB::transaction(function () use ($validated, $quote, $priceChanged, $clientTotal, $userId) {
                // Addresses used to be dropped (not fillable on Order), so orders
                // had no delivery address. Save them as the customer's addresses
                // (re-using an identical one) and link them to the order.
                $shippingId = $this->saveAddress($userId, $validated['shipping_address']);
                $billingId  = $validated['billing_address_same_as_shipping']
                    ? $shippingId
                    : $this->saveAddress($userId, $validated['billing_address']);

                $order = Order::create([
                    'id'                               => Str::uuid()->toString(),
                    'user_id'                          => $userId,
                    'status'                           => 'pending',
                    'payment_status'                   => 'unpaid',
                    'total_amount'                     => $quote['total'],
                    'shipping_cost'                    => $quote['shipping'],
                    'tax_cost'                         => $quote['tax'],
                    'order_date'                       => now(),
                    'payment_method'                   => $validated['payment_method'],
                    'shipping_address_id'              => $shippingId,
                    'billing_address_id'               => $billingId,
                    'billing_address_same_as_shipping' => $validated['billing_address_same_as_shipping'],
                    'delivery_date'                    => $validated['delivery_date'] ?? now()->addDays(7),
                ]);

                foreach ($quote['items'] as $item) {
                    $order->items()->create([
                        'order_id'           => $order->id,
                        'product_id'         => $item['product_id'],
                        'title'              => $item['title'],
                        'price'              => $item['price'],
                        'quantity'           => $item['quantity'],
                        'variation_id'       => $item['variation_id'] ?? null,
                        'image'              => $item['image'] ?? null,
                        'brand_name'         => $item['brand_name'] ?? null,
                        'selected_variation' => isset($item['selected_variation'])
                            ? json_encode($item['selected_variation'])
                            : null,
                    ]);
                }

                if ($priceChanged) {
                    Log::info('Order priced differently from the app', [
                        'order_id'     => $order->id,
                        'client_total' => $clientTotal,
                        'server_total' => $quote['total'],
                    ]);
                }

                // ── Notify admins (existing email/database notification) ──────
                try {
                    $admins = \App\Models\User::role('Admin')->get();
                    if ($admins->isNotEmpty()) {
                        Notification::send($admins, new OrderPlacedNotification($order));
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to send admin order notification', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }

                // ── Notify the customer via FCM push ─────────────────────────
                try {
                    $this->orderNotificationService->notifyOrderPlaced($order);
                } catch (\Exception $e) {
                    Log::warning('FCM order placed notification failed', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }

                Log::info('Order created', ['order_id' => $order->id, 'total' => $quote['total']]);

                return response()->json([
                    'success'       => true,
                    'order'         => $order->load(['items.product', 'shippingAddress', 'billingAddress']),
                    'pricing'       => [
                        'subtotal' => $quote['subtotal'],
                        'shipping' => $quote['shipping'],
                        'tax'      => $quote['tax'],
                        'total'    => $quote['total'],
                    ],
                    'price_changed' => $priceChanged,
                    'message'       => $priceChanged
                        ? 'Some prices changed since you added them to your cart. Your order total has been updated.'
                        : 'Order created successfully. Please proceed to payment.',
                ], 201);
            });

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Please check your order details.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error placing order: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error placing order. Please try again.',
            ], 500);
        }
    }

    /** Re-use the customer's identical saved address, or save a new one. Returns its id. */
    protected function saveAddress(?int $userId, array $a): ?int
    {
        $match = [
            'user_id' => $userId,
            'name'    => trim((string) ($a['name'] ?? '')),
            'street'  => trim((string) ($a['street'] ?? '')),
            'city'    => trim((string) ($a['city'] ?? '')),
            'country' => trim((string) ($a['country'] ?? '')),
        ];
        $extra = array_filter([
            'state'           => $a['state'] ?? null,
            'lga'             => $a['lga'] ?? null,
            'landmark'        => $a['landmark'] ?? null,
            'address_type'    => $a['address_type'] ?? null,
            'postal_code'     => $a['postal_code'] ?? null,
            'phone_number'    => $a['phone_number'] ?? null,
            'alternate_phone' => $a['alternate_phone'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $address = Address::firstOrCreate($match, $extra + ['is_default' => false]);
        // Keep phone / LGA / landmark current if the customer edited them.
        $address->fill($extra);
        if ($address->isDirty()) {
            $address->save();
        }
        return $address->id;
    }

    /**
     * GET /api/checkout/settings — the rates the server prices orders with, so
     * the app's checkout shows the same shipping / tax / total.
     */
    public function checkoutSettings(OrderPricingService $pricing)
    {
        return response()->json(['success' => true, 'data' => $pricing->settings()]);
    }

    /**
     * POST /api/checkout/quote — price a cart exactly as store() will.
     * Body: { items: [{product_id, variation_id?, quantity, price?}] }
     */
    public function quote(Request $request, OrderPricingService $pricing)
    {
        $data = $request->validate([
            'items'                => 'required|array|min:1|max:100',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity'     => 'required|integer|min:1|max:1000',
            'items.*.price'        => 'nullable|numeric|min:0',
        ]);

        try {
            $q = $pricing->price($data['items']);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json(['success' => true, 'data' => [
            'items'         => collect($q['items'])->map(fn ($i) => [
                'product_id'   => $i['product_id'],
                'variation_id' => $i['variation_id'] ?? null,
                'quantity'     => $i['quantity'],
                'price'        => $i['price'],
            ])->values(),
            'subtotal'      => $q['subtotal'],
            'shipping'      => $q['shipping'],
            'tax'           => $q['tax'],
            'total'         => $q['total'],
            'price_changed' => $q['changed'],
        ]]);
    }

    /** Admin / staff accounts may set any status; customers may only cancel. */
    protected function isStaff($user): bool
    {
        if (!$user || !method_exists($user, 'getRoleNames')) {
            return false;
        }
        return $user->getRoleNames()
            ->map(fn ($r) => strtolower($r))
            ->diff(['customer', 'user'])
            ->isNotEmpty();
    }

    /**
     * Update order status and notify the customer via FCM.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status'             => 'required|in:pending,processing,shipped,delivered,cancelled',
            'send_notifications' => 'sometimes|boolean',
        ]);

        try {
            $order     = Order::with('user')->findOrFail($id);
            $oldStatus = $order->status;
            $newStatus = $request->status;

            $isStaff = $this->isStaff(Auth::user());
            $isOwner = (string) $order->user_id === (string) Auth::id();

            if (!$isStaff && !$isOwner) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }
            // Customers can only cancel their own order, and only before it ships.
            if (!$isStaff && ($newStatus !== 'cancelled' || !$order->canBeCancelled())) {
                return response()->json([
                    'success' => false,
                    'message' => $newStatus === 'cancelled'
                        ? 'This order can no longer be cancelled.'
                        : 'You can only cancel your order.',
                ], 403);
            }

            $legacyNotificationResult = [];
            $fcmResult                = [];

            DB::transaction(function () use (
                $order, $newStatus, $request,
                &$legacyNotificationResult, &$fcmResult
            ) {
                $order->update(['status' => $newStatus]);

                // Existing legacy notification (email / database)
                if ($request->boolean('send_notifications', true)) {
                    $legacyNotificationResult = $this->notificationService
                        ->sendOrderStatusUpdate($order, $newStatus);
                }

                // FCM push notification to the customer
                try {
                    $fcmResult = $this->orderNotificationService
                        ->notifyOrderStatusUpdate($order, $newStatus);
                } catch (\Exception $e) {
                    Log::warning('FCM status update notification failed', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            });

            Log::info('Order status updated', [
                'order_id'   => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'fcm_sent'   => $fcmResult['sent'] ?? 0,
            ]);

            return response()->json([
                'success'       => true,
                'message'       => 'Order status updated successfully',
                'data'          => $order->fresh(['items.product', 'shippingAddress', 'billingAddress']),
                'notifications' => $legacyNotificationResult,
                'fcm'           => $fcmResult,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating order status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ── All other methods unchanged ───────────────────────────────────────────

    public function getBarcode($id)
    {
        try {
            $order = Order::with('user')->findOrFail($id);

            if (!$this->isStaff(Auth::user()) && (string) $order->user_id !== (string) Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            if (!$order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order must be paid to generate barcode',
                ], 400);
            }

            $barcodeResult = $this->barcodeService->generateBarcodeForOrder($order);

            if (!$barcodeResult['success']) {
                return response()->json(['success' => false, 'message' => 'Failed to generate barcode'], 500);
            }

            return response()->json([
                'success' => true,
                'barcode' => [
                    'url'      => $barcodeResult['barcode_url'],
                    'data_url' => $barcodeResult['barcode_data_url'],
                    'order_id' => $order->id,
                    'data'     => json_decode($barcodeResult['barcode_data'], true),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating barcode: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to generate barcode'], 500);
        }
    }

    public function scanBarcode(Request $request)
    {
        try {
            $request->validate(['barcode_data' => 'required|string']);

            $parsedData = $this->barcodeService->parseBarcodeData($request->barcode_data);

            if (!$parsedData['valid']) {
                return response()->json(['success' => false, 'message' => 'Invalid barcode'], 400);
            }

            $order = Order::with(['user', 'items.product', 'shippingAddress', 'billingAddress'])
                ->find($parsedData['order_id']);

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Order not found'], 404);
            }

            if (Auth::user()->hasRole('customer') && $order->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            return response()->json([
                'success'      => true,
                'order'        => $order,
                'barcode_data' => $parsedData,
            ]);

        } catch (\Exception $e) {
            Log::error('Error scanning barcode: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to scan barcode'], 500);
        }
    }

    public function index()
    {
        try {
            $orders = Order::with(['items.product', 'shippingAddress', 'billingAddress', 'transactions'])
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $orders], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to fetch orders: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $order = Order::with(['items.product', 'shippingAddress', 'billingAddress', 'transactions'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json(['success' => true, 'data' => $order], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Order not found or access denied.'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching order: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status'        => 'sometimes|in:pending,processing,shipped,delivered,cancelled',
            'delivery_date' => 'nullable|date',
        ]);

        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);

            // Customers may only cancel (before shipping). Other status changes
            // such as "delivered" are for staff via /orders/{id}/status.
            if ($request->filled('status') && $request->input('status') !== $order->status) {
                if ($request->input('status') !== 'cancelled' || !$order->canBeCancelled()) {
                    return response()->json(['success' => false, 'message' => 'You can only cancel this order before it ships.'], 403);
                }
                $order->status = 'cancelled';
                $order->save();
            }

            return response()->json([
                'success' => true,
                'data'    => $order->load(['items.product', 'shippingAddress', 'billingAddress']),
                'message' => 'Order updated successfully!',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Order not found or access denied.'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating order: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);

            if (!$order->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order cannot be cancelled at this stage.',
                ], 400);
            }

            $order->delete();

            return response()->json(['success' => true, 'message' => 'Order cancelled successfully!'], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Order not found or access denied.'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting order: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error deleting order: ' . $e->getMessage()], 500);
        }
    }

    public function patch($id, Request $request)
    {
        try {
            $order = Order::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Never mass-assign from the request: that let a customer set
            // payment_status = "paid" or change the total. Only cancelling is allowed.
            if ($request->filled('status') && $request->input('status') !== $order->status) {
                if ($request->input('status') !== 'cancelled' || !$order->canBeCancelled()) {
                    return response()->json(['success' => false, 'message' => 'You can only cancel this order before it ships.'], 403);
                }
                $order->update(['status' => 'cancelled']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
                'data'    => $order->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
