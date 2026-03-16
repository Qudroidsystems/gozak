<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Handles all FCM push notifications related to orders.
 *
 * Triggers:
 *  - User places a new order        → notify the user (order confirmed)
 *  - Admin changes order status     → notify the user (status update)
 */
class OrderNotificationService
{
    protected FcmService $fcm;

    public function __construct(FcmService $fcm)
    {
        $this->fcm = $fcm;
    }

    // ── Status display helpers ────────────────────────────────────────────────

    private static array $statusEmoji = [
        'pending'    => '🕐',
        'processing' => '⚙️',
        'shipped'    => '🚚',
        'delivered'  => '✅',
        'cancelled'  => '❌',
    ];

    private static array $statusTitles = [
        'pending'    => 'Order Received',
        'processing' => 'Order Processing',
        'shipped'    => 'Order Shipped!',
        'delivered'  => 'Order Delivered!',
        'cancelled'  => 'Order Cancelled',
    ];

    private static array $statusBodies = [
        'pending'    => 'We\'ve received your order and it\'s being reviewed.',
        'processing' => 'Your order is being prepared for shipment.',
        'shipped'    => 'Your order is on its way! Track it in the app.',
        'delivered'  => 'Your order has been delivered. Enjoy! 🎉',
        'cancelled'  => 'Your order has been cancelled. Contact support if needed.',
    ];

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Notify the user that their order was successfully placed.
     * Called from APIOrderController::store()
     */
    public function notifyOrderPlaced(Order $order): array
    {
        $user = User::find($order->user_id);
        if (!$user || !$user->hasActiveFcmToken()) {
            Log::info('OrderNotification: user has no FCM token', ['order_id' => $order->id]);
            return ['sent' => 0, 'failed' => 0];
        }

        if (!($user->order_updates_enabled ?? true)) {
            Log::info('OrderNotification: user has order updates disabled', ['user_id' => $user->id]);
            return ['sent' => 0, 'failed' => 0];
        }

        $invoiceRef = $order->invoice_number ?? substr($order->id, 0, 8);
        $total      = number_format($order->total_amount, 0);

        $title = '🛍️ Order Confirmed!';
        $body  = "Order #{$invoiceRef} placed for ₦{$total}. We'll update you when it ships.";

        $data = [
            'type'           => 'order_placed',
            'order_id'       => (string) $order->id,
            'invoice_number' => (string) ($order->invoice_number ?? ''),
            'status'         => 'pending',
            'total_amount'   => (string) $order->total_amount,
            'route'          => '/orders/' . $order->id,
        ];

        return $this->sendToUser($user, $title, $body, $data, 'order_placed');
    }

    /**
     * Notify the user about an order status change.
     * Called from APIOrderController::updateStatus() and OrderController::updateStatus()
     */
    public function notifyOrderStatusUpdate(Order $order, string $newStatus): array
    {
        $user = $order->user ?? User::find($order->user_id);

        if (!$user || !$user->hasActiveFcmToken()) {
            Log::info('OrderNotification: user has no FCM token', [
                'order_id' => $order->id,
                'status'   => $newStatus,
            ]);
            return ['sent' => 0, 'failed' => 0];
        }

        if (!($user->order_updates_enabled ?? true)) {
            return ['sent' => 0, 'failed' => 0];
        }

        $emoji      = self::$statusEmoji[$newStatus]  ?? '📦';
        $statusTitle = self::$statusTitles[$newStatus] ?? 'Order Update';
        $statusBody  = self::$statusBodies[$newStatus] ?? "Your order status is now: {$newStatus}.";
        $invoiceRef  = $order->invoice_number ?? substr($order->id, 0, 8);

        $title = "{$emoji} {$statusTitle}";
        $body  = "Order #{$invoiceRef}: {$statusBody}";

        $data = [
            'type'           => 'order_status_update',
            'order_id'       => (string) $order->id,
            'invoice_number' => (string) ($order->invoice_number ?? ''),
            'status'         => $newStatus,
            'total_amount'   => (string) $order->total_amount,
            'route'          => '/orders/' . $order->id,
        ];

        return $this->sendToUser($user, $title, $body, $data, 'order_status_update');
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function sendToUser(
        User   $user,
        string $title,
        string $body,
        array  $data,
        string $logLabel
    ): array {
        $result = $this->fcm->sendToToken($user->fcm_token, $title, $body, $data);

        if ($result['success']) {
            $user->recordNotificationSent();
            Log::info("OrderNotification [{$logLabel}] sent", [
                'user_id'  => $user->id,
                'order_id' => $data['order_id'] ?? null,
                'status'   => $data['status']   ?? null,
            ]);
            return ['sent' => 1, 'failed' => 0];
        }

        // Clean up stale tokens
        $errorStatus = $result['response']['error']['status'] ?? '';
        if (in_array($errorStatus, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
            $user->update(['fcm_token' => null]);
            Log::info("OrderNotification: stale token cleared for user {$user->id}");
        } else {
            Log::warning("OrderNotification [{$logLabel}] failed", [
                'user_id' => $user->id,
                'error'   => $result['error'] ?? ($result['response'] ?? 'unknown'),
            ]);
        }

        return ['sent' => 0, 'failed' => 1];
    }
}
