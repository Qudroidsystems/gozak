<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** /api/orders/{id}/tracking and /api/orders/{id}/confirm-delivery */
class APIOrderTrackingController extends Controller
{
    public function __construct(private OrderTrackingService $tracking)
    {
    }

    public function show($id)
    {
        $order = Order::where('user_id', Auth::id())->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->tracking->tracking($order)]);
    }

    public function confirm(Request $request, $id)
    {
        $order = Order::where('user_id', Auth::id())->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
        if (!$this->tracking->canConfirm($order)) {
            return response()->json([
                'success' => false,
                'message' => $order->received_confirmed_at
                    ? 'You already confirmed this order.'
                    : 'You can confirm once your order has been shipped.',
            ], 422);
        }

        $this->tracking->confirmReceived($order, 'customer');
        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Thanks! We\'ve marked your order as received.',
            'data'    => $this->tracking->tracking($order),
        ]);
    }
}
