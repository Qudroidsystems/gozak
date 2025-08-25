<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class APIOrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $orders = Order::with(['items.product', 'shippingAddress', 'billingAddress'])
                ->where('user_id', Auth::id())
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
     * Store a newly created order.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate request
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

            // Set billing_address to shipping_address if billing_address_same_as_shipping is true
            if ($validated['billing_address_same_as_shipping']) {
                $validated['billing_address'] = $validated['shipping_address'];
            }

            // Start a transaction to ensure data consistency
            return DB::transaction(function () use ($validated) {
                // Create order without client-provided id
                $orderData = array_merge(
                    Arr::except($validated, ['items', 'id']),
                    ['id' => Str::uuid()->toString()]
                );

                $order = Order::create($orderData);

                // Create order items
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

                Log::info('APIOrderController: Order created successfully', ['order_id' => $order->id]);

                return response()->json(['success' => true, 'order' => $order], 201);
            });
        } catch (\Exception $e) {
            Log::error('APIOrderController: Error placing order: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error placing order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display a specific order.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $order = Order::with(['items.product', 'shippingAddress', 'billingAddress'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $order,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Order not found: ' . $e->getMessage());
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
     * Update an existing order (e.g., status).
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'sometimes|in:pending,shipped,delivered,cancelled',
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
     * Remove an order.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);
            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully!',
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
}