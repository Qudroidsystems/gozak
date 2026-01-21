<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Address;
use App\Models\OrderItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\BarcodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderPlacedNotification;

/**
 * @group Orders
 *
 * Manage user orders: creation, status updates, barcode generation & scanning.
 *
 * All endpoints require Sanctum authentication (Bearer token).
 */
class APIOrderController extends Controller
{
    protected $notificationService;
    protected $barcodeService;

    public function __construct(NotificationService $notificationService, BarcodeService $barcodeService)
    {
        $this->notificationService = $notificationService;
        $this->barcodeService = $barcodeService;
    }

    /**
     * Create a new order
     *
     * @authenticated
     *
     * @bodyParam user_id integer required User ID placing the order
     * @bodyParam status string required One of: pending, shipped, delivered, cancelled
     * @bodyParam total_amount numeric required Grand total
     * @bodyParam total numeric required Same as total_amount (legacy field)
     * @bodyParam shipping_cost numeric required
     * @bodyParam tax_cost numeric required
     * @bodyParam order_date date required Order placement date
     * @bodyParam payment_method string required e.g. "card", "wallet", "bank_transfer"
     * @bodyParam shipping_address array required
     * @bodyParam shipping_address.name string required
     * @bodyParam shipping_address.street string required
     * @bodyParam shipping_address.city string required
     * @bodyParam shipping_address.country string required
     * @bodyParam shipping_address.phone_number string|null optional
     * @bodyParam billing_address_same_as_shipping boolean required
     * @bodyParam billing_address array required if billing_address_same_as_shipping = false
     * @bodyParam delivery_date date|null Expected delivery date
     * @bodyParam items array required Minimum 1 item
     * @bodyParam items.*.product_id integer required
     * @bodyParam items.*.title string required Product title
     * @bodyParam items.*.price numeric required Unit price
     * @bodyParam items.*.quantity integer required ≥1
     * @bodyParam items.*.variation_id integer|null Variation ID
     * @bodyParam items.*.image string|null Image URL/path
     * @bodyParam items.*.brand_name string|null Brand name
     * @bodyParam items.*.selected_variation array|null Selected attributes
     *
     * @response 201 {
     *     "success": true,
     *     "order": { ... full order with items ... },
     *     "message": "Order created successfully. Please proceed to payment."
     * }
     * @response 422 validation errors
     * @response 500 transaction or server error
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'status' => 'required|in:pending,shipped,delivered,cancelled',
                'total_amount' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'shipping_cost' => 'required|numeric|min:0',
                'tax_cost' => 'required|numeric|min:0',
                'order_date' => 'required|date',
                'payment_method' => 'required|string',
                'shipping_address' => 'required|array',
                'shipping_address.name' => 'required|string',
                'shipping_address.street' => 'required|string',
                'shipping_address.city' => 'required|string',
                'shipping_address.country' => 'required|string',
                'shipping_address.phone_number' => 'nullable|string',
                'billing_address' => 'required_if:billing_address_same_as_shipping,false|array',
                'billing_address.name' => 'required_if:billing_address_same_as_shipping,false|string',
                'billing_address.street' => 'required_if:billing_address_same_as_shipping,false|string',
                'billing_address.city' => 'required_if:billing_address_same_as_shipping,false|string',
                'billing_address.country' => 'required_if:billing_address_same_as_shipping,false|string',
                'billing_address.phone_number' => 'nullable|string',
                'billing_address_same_as_shipping' => 'required|boolean',
                'delivery_date' => 'nullable|date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.title' => 'required|string',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.variation_id' => 'nullable|exists:product_variations,id',
                'items.*.image' => 'nullable|string',
                'items.*.brand_name' => 'nullable|string',
                'items.*.selected_variation' => 'nullable|array',
            ]);

            Log::info('APIOrderController: Validated order data', $validated);

            if ($validated['billing_address_same_as_shipping']) {
                $validated['billing_address'] = $validated['shipping_address'];
            }

            return DB::transaction(function () use ($validated) {
                $orderData = array_merge(
                    Arr::except($validated, ['items', 'id']),
                    [
                        'id' => Str::uuid()->toString(),
                        'payment_status' => 'unpaid'
                    ]
                );

                $order = Order::create($orderData);

                foreach ($validated['items'] as $item) {
                    $order->items()->create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'title' => $item['title'],
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'variation_id' => $item['variation_id'],
                        'image' => $item['image'],
                        'brand_name' => $item['brand_name'],
                        'selected_variation' => $item['selected_variation'] ? json_encode($item['selected_variation']) : null,
                    ]);
                }

                try {
                    $admins = \App\Models\User::role('Admin')->get();

                    if ($admins->isNotEmpty()) {
                        Notification::send($admins, new OrderPlacedNotification($order));
                        Log::info('New order notification sent to admins', [
                            'order_id' => $order->id,
                            'admin_count' => $admins->count()
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to send new order notification to admins', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                Log::info('APIOrderController: Order created successfully', ['order_id' => $order->id]);

                return response()->json([
                    'success' => true,
                    'order' => $order->load(['items.product', 'shippingAddress', 'billingAddress']),
                    'message' => 'Order created successfully. Please proceed to payment.'
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('APIOrderController: Error placing order: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error placing order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update order status
     *
     * @authenticated
     *
     * @urlParam id string required Order UUID
     *
     * @bodyParam status string required pending|processing|shipped|delivered|cancelled
     * @bodyParam send_notifications boolean Whether to send notifications (default: true)
     *
     * @response 200 status updated successfully
     * @response 403 access denied (wrong user)
     * @response 500 update failed
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'send_notifications' => 'sometimes|boolean',
        ]);

        try {
            $order = Order::with('user')->findOrFail($id);
            $oldStatus = $order->status;
            $newStatus = $request->status;

            if (Auth::user()->hasRole('customer') && $order->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $notificationResult = [];

            DB::transaction(function () use ($order, $newStatus, $request, &$notificationResult) {
                $order->update(['status' => $newStatus]);

                if ($request->boolean('send_notifications', true)) {
                    $notificationResult = $this->notificationService->sendOrderStatusUpdate($order, $newStatus);
                }
            });

            Log::info("Order status updated with notifications", [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $order->user_id,
                'notifications_sent' => !empty($notificationResult),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order->fresh(['items.product', 'shippingAddress', 'billingAddress']),
                'notifications' => $notificationResult,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating order status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate barcode for a paid order
     *
     * @authenticated
     *
     * @urlParam id string required Order UUID
     *
     * @response 200 barcode information (URL, data URL, parsed data)
     * @response 400 order not paid
     * @response 403 access denied
     * @response 404 order not found
     * @response 500 generation failed
     */
    public function getBarcode($id)
    {
        try {
            $order = Order::with('user')->findOrFail($id);

            if (Auth::user()->hasRole('customer') && $order->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            if (!$order->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order must be paid to generate barcode',
                ], 400);
            }

            $barcodeResult = $this->barcodeService->generateBarcodeForOrder($order);

            if (!$barcodeResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate barcode',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'barcode' => [
                    'url' => $barcodeResult['barcode_url'],
                    'data_url' => $barcodeResult['barcode_data_url'],
                    'order_id' => $order->id,
                    'data' => json_decode($barcodeResult['barcode_data'], true),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating barcode: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate barcode',
            ], 500);
        }
    }

    /**
     * Scan / verify barcode data
     *
     * @authenticated
     *
     * @bodyParam barcode_data string required Scanned barcode content
     *
     * @response 200 order + parsed barcode information
     * @response 400 invalid barcode
     * @response 403 access denied
     * @response 404 order not found
     * @response 500 scan failed
     */
    public function scanBarcode(Request $request)
    {
        try {
            $request->validate([
                'barcode_data' => 'required|string',
            ]);

            $parsedData = $this->barcodeService->parseBarcodeData($request->barcode_data);

            if (!$parsedData['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid barcode',
                ], 400);
            }

            $order = Order::with(['user', 'items.product', 'shippingAddress', 'billingAddress'])
                ->find($parsedData['order_id']);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            if (Auth::user()->hasRole('customer') && $order->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'order' => $order,
                'barcode_data' => $parsedData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error scanning barcode: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to scan barcode',
            ], 500);
        }
    }

    /**
     * List all orders for the authenticated user
     *
     * @authenticated
     *
     * @response 200 list of orders with relations
     * @response 500 fetch error
     */
    public function index()
    {
        try {
            $orders = Order::with(['items.product', 'shippingAddress', 'billingAddress', 'transactions'])
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $orders,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show details of a single order
     *
     * @authenticated
     *
     * @urlParam id string required Order UUID
     *
     * @response 200 order with items, addresses, transactions
     * @response 404 order not found or access denied
     */
    public function show($id)
    {
        try {
            $order = Order::with(['items.product', 'shippingAddress', 'billingAddress', 'transactions'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $order,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or access denied.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update order (limited fields)
     *
     * @authenticated
     *
     * @urlParam id string required Order UUID
     *
     * @bodyParam status string|null pending|processing|shipped|delivered|cancelled
     * @bodyParam delivery_date date|null
     *
     * @response 200 updated order
     * @response 404 not found or access denied
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'sometimes|in:pending,processing,shipped,delivered,cancelled',
            'delivery_date' => 'nullable|date',
        ]);

        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);

            $order->update([
                'status' => $request->input('status', $order->status),
                'delivery_date' => $request->input('delivery_date', $order->delivery_date),
            ]);

            return response()->json([
                'success' => true,
                'data' => $order->load(['items.product', 'shippingAddress', 'billingAddress']),
                'message' => 'Order updated successfully!',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Order not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Order not found or access denied.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel (soft-delete) an order
     *
     * @authenticated
     *
     * @urlParam id string required Order UUID
     *
     * @response 200 order cancelled
     * @response 400 cannot cancel at this stage
     * @response 404 not found or access denied
     */
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

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully!',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Order not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Order not found or access denied.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Partial update of an order (PATCH)
     *
     * @authenticated
     *
     * @urlParam id string required Order UUID
     *
     * @response 200 order updated
     * @response 500 update failed
     */
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
                'data' => $order->fresh()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
