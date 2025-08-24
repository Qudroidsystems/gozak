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

class APIOrderController extends Controller
{
    /**
     * Display a listing of the user's orders.
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
     */
    public function store(Request $request)
    {
        $request->validate([
            'total_amount' => 'required|numeric|min:0',
            'shipping_cost' => 'required|numeric|min:0',
            'tax_cost' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'billing_address_same_as_shipping' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.title' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variation_id' => 'nullable|string',
            'items.*.image' => 'nullable|string',
            'items.*.brand_name' => 'nullable|string',
            'items.*.selected_variation' => 'nullable|array',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string',
            'shipping_address.street' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.country' => 'required|string',
            'billing_address' => 'nullable|array',
            'billing_address.name' => 'required_if:billing_address_same_as_shipping,false|string',
            'billing_address.street' => 'required_if:billing_address_same_as_shipping,false|string',
            'billing_address.city' => 'required_if:billing_address_same_as_shipping,false|string',
            'billing_address.country' => 'required_if:billing_address_same_as_shipping,false|string',
        ]);

        try {
            DB::beginTransaction();

            // Create or update shipping address
            $shippingAddressData = $request->shipping_address;
            $shippingAddressData['user_id'] = Auth::id();
            $shippingAddress = Address::create($shippingAddressData);

            // Create billing address if not same as shipping
            $billingAddress = null;
            if (!$request->billing_address_same_as_shipping && $request->billing_address) {
                $billingAddressData = $request->billing_address;
                $billingAddressData['user_id'] = Auth::id();
                $billingAddress = Address::create($billingAddressData);
            }

            // Create order
            $order = Order::create([
                'id' => Str::uuid()->toString(), // Generate UUID
                'user_id' => Auth::id(),
                'status' => $request->status ?? 'pending',
                'total_amount' => $request->total_amount,
                'shipping_cost' => $request->shipping_cost,
                'tax_cost' => $request->tax_cost,
                'order_date' => now(),
                'payment_method' => $request->payment_method,
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress ? $billingAddress->id : $shippingAddress->id,
                'billing_address_same_as_shipping' => $request->billing_address_same_as_shipping ?? true,
                'delivery_date' => $request->delivery_date ? now()->addDays(3) : null, // Example
            ]);

            // Create order items
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'title' => $item['title'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'image' => $item['image'] ?? null,
                    'brand_name' => $item['brand_name'] ?? null,
                    'selected_variation' => $item['selected_variation'] ? json_encode($item['selected_variation']) : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load(['items.product', 'shippingAddress', 'billingAddress']),
                'message' => 'Order placed successfully!',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error placing order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error placing order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a specific order.
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
        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Order not found or access denied.',
            ], 404);
        }
    }

    /**
     * Update an existing order (e.g., status).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'sometimes|in:pending,shipped,delivered,cancelled',
        ]);

        try {
            $order = Order::where('user_id', Auth::id())->findOrFail($id);
            $order->update([
                'status' => $request->status ?? $order->status,
            ]);
            return response()->json([
                'success' => true,
                'data' => $order->load(['items.product', 'shippingAddress', 'billingAddress']),
                'message' => 'Order updated successfully!',
            ], 200);
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
        } catch (\Exception $e) {
            Log::error('Error deleting order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting order: ' . $e->getMessage(),
            ], 500);
        }
    }
}