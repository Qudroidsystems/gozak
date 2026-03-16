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
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id'                          => 'required|exists:users,id',
                'status'                           => 'required|in:pending,shipped,delivered,cancelled',
                'total_amount'                     => 'required|numeric|min:0',
                'total'                            => 'required|numeric|min:0',
                'shipping_cost'                    => 'required|numeric|min:0',
                'tax_cost'                         => 'required|numeric|min:0',
                'order_date'                       => 'required|date',
                'payment_method'                   => 'required|string',
                'shipping_address'                 => 'required|array',
                'shipping_address.name'            => 'required|string',
                'shipping_address.street'          => 'required|string',
                'shipping_address.city'            => 'required|string',
                'shipping_address.country'         => 'required|string',
                'shipping_address.phone_number'    => 'nullable|string',
                'billing_address'                  => 'required_if:billing_address_same_as_shipping,false|array',
                'billing_address.name'             => 'required_if:billing_address_same_as_shipping,false|string',
                'billing_address.street'           => 'required_if:billing_address_same_as_shipping,false|string',
                'billing_address.city'             => 'required_if:billing_address_same_as_shipping,false|string',
                'billing_address.country'          => 'required_if:billing_address_same_as_shipping,false|string',
                'billing_address.phone_number'     => 'nullable|string',
                'billing_address_same_as_shipping' => 'required|boolean',
                'delivery_date'                    => 'nullable|date',
                'items'                            => 'required|array|min:1',
                'items.*.product_id'               => 'required|exists:products,id',
                'items.*.title'                    => 'required|string',
                'items.*.price'                    => 'required|numeric|min:0',
                'items.*.quantity'                 => 'required|integer|min:1',
                'items.*.variation_id'             => 'nullable|exists:product_variations,id',
                'items.*.image'                    => 'nullable|string',
                'items.*.brand_name'               => 'nullable|string',
                'items.*.selected_variation'       => 'nullable|array',
            ]);

            if ($validated['billing_address_same_as_shipping']) {
                $validated['billing_address'] = $validated['shipping_address'];
            }

            return DB::transaction(function () use ($validated) {
                $orderData = array_merge(
                    Arr::except($validated, ['items', 'id']),
                    [
                        'id'             => Str::uuid()->toString(),
                        'payment_status' => 'unpaid',
                    ]
                );

                $order = Order::create($orderData);

                foreach ($validated['items'] as $item) {
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
                    // FCM failure must never fail the order creation
                    Log::warning('FCM order placed notification failed', [
                        'order_id' => $order->id,
                        'error'    => $e->getMessage(),
                    ]);
                }

                Log::info('Order created', ['order_id' => $order->id]);

                return response()->json([
                    'success' => true,
                    'order'   => $order->load(['items.product', 'shippingAddress', 'billingAddress']),
                    'message' => 'Order created successfully. Please proceed to payment.',
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Error placing order: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error placing order: ' . $e->getMessage(),
            ], 500);
        }
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

            if (Auth::user()->hasRole('customer') && $order->user_id !== Auth::id()) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
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

            if (Auth::user()->hasRole('customer') && $order->user_id !== Auth::id()) {
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
            $order->update([
                'status'        => $request->input('status', $order->status),
                'delivery_date' => $request->input('delivery_date', $order->delivery_date),
            ]);

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

            $order->update($request->all());

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
