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

    /** GET /api/orders/{id}/live-tracking: the rider on the map, ETA and delivery code (marketplace deliveries). */
    public function live($id, \App\Services\Logistics\LogisticsService $logistics)
    {
        $order = Order::where('user_id', Auth::id())->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
        try {
            $data = $logistics->liveTracking($order);
        } catch (\App\Services\Logistics\LogisticsException $e) {
            return response()->json(['success' => false, 'message' => 'Live tracking is not available right now. Please try again shortly.'], 503);
        }
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'There is no rider to follow on this order yet.'], 404);
        }
        return response()->json(['success' => true, 'data' => $data]);
    }

    /** POST /api/orders/{id}/rider-reply {reply: ready|need_time|call_me|someone_else, note?} */
    public function reply(Request $request, $id, \App\Services\Logistics\LogisticsService $logistics)
    {
        $data = $request->validate(['reply' => 'required|string|in:ready,need_time,call_me,someone_else', 'note' => 'nullable|string|max:140']);
        $order = Order::where('user_id', Auth::id())->find($id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }
        try {
            $msg = $logistics->replyToRider($order, $data['reply'], $data['note'] ?? null);
        } catch (\App\Services\Logistics\LogisticsException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => $msg]);
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
