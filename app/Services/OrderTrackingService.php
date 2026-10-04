<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderSetting;
use App\Models\OrderStatusHistory;
use App\Models\UserNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Order tracking for the app and admin:
 *  - records every status change (who, when) → the timeline
 *  - writes an in-app notification for the customer on every change
 *    (push/FCM is still sent by OrderNotificationService)
 *  - customer "I've received my order" confirmation
 *  - auto-confirm delivery after N days (admin setting)
 */
class OrderTrackingService
{
    public const STEPS = ['pending', 'processing', 'shipped', 'delivered'];

    public const LABELS = [
        'pending'    => 'Order placed',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',
    ];

    private const BODIES = [
        'pending'    => 'We\'ve received your order and it\'s being reviewed.',
        'processing' => 'Your order is being prepared for shipment.',
        'shipped'    => 'Your order is on its way. Tap "I\'ve received it" when it arrives.',
        'delivered'  => 'Your order has been delivered. Enjoy!',
        'cancelled'  => 'Your order has been cancelled. Contact support if you need help.',
    ];

    // ── Hooks (called from model events in AppServiceProvider) ──────────────

    /** Stamp shipped_at / delivered_at in the same save as the status change. */
    public function stampTimestamps(Order $order): void
    {
        if (!$order->isDirty('status')) {
            return;
        }
        if ($order->status === 'shipped' && empty($order->shipped_at)) {
            $order->shipped_at = now();
        }
        if ($order->status === 'delivered') {
            if (empty($order->delivered_at)) {
                $order->delivered_at = now();
            }
            if (empty($order->shipped_at)) {
                $order->shipped_at = $order->delivered_at;
            }
            // Admin marking it delivered counts as a confirmation unless the
            // customer (or auto-confirm) does it in the same save.
            if (empty($order->received_confirmed_at) && empty($order->delivery_confirmed_by)) {
                $order->delivery_confirmed_by = 'admin';
            }
        }
    }

    public function orderCreated(Order $order): void
    {
        $this->history($order, null, $order->status ?: 'pending', 'customer', $order->user_id);
        $ref = $this->ref($order);
        $this->inApp($order, 'order_placed', 'Order confirmed', "Order #{$ref} placed for ₦" . number_format((float) $order->total_amount) . '. We\'ll keep you posted.');
    }

    public function statusChanged(Order $order, ?string $from, string $to): void
    {
        [$actorType, $actorId] = $this->actor($order);
        $note = null;
        if ($to === 'delivered' && $order->delivery_confirmed_by === 'customer') {
            $note = 'Customer confirmed they received the order';
        } elseif ($to === 'delivered' && $order->delivery_confirmed_by === 'auto') {
            $note = 'Automatically confirmed after ' . OrderSetting::current()->auto_confirm_days . ' days';
        }
        $this->history($order, $from, $to, $actorType, $actorId, $note);

        $ref   = $this->ref($order);
        $title = (self::LABELS[$to] ?? ucfirst($to));
        $body  = self::BODIES[$to] ?? "Your order status is now {$to}.";
        if ($to === 'delivered' && $order->delivery_confirmed_by === 'customer') {
            $title = 'Thanks for confirming';
            $body  = 'You confirmed you received this order. We hope you love it!';
        }
        $this->inApp($order, 'order_status_update', $title, "Order #{$ref}: {$body}", ['status' => $to]);
    }

    // ── Customer confirmation ───────────────────────────────────────────────

    public function canConfirm(Order $order): bool
    {
        return empty($order->received_confirmed_at)
            && in_array($order->status, ['shipped', 'delivered'], true);
    }

    /** Customer taps "I've received my order". */
    public function confirmReceived(Order $order, string $by = 'customer'): Order
    {
        if ($order->status === 'delivered') {
            // Already marked delivered by admin — just record the confirmation.
            $order->forceFill(['received_confirmed_at' => now(), 'delivery_confirmed_by' => $by])->save();
            $this->history($order, 'delivered', 'delivered', $by === 'customer' ? 'customer' : 'system', $by === 'customer' ? $order->user_id : null,
                $by === 'customer' ? 'Customer confirmed they received the order' : 'Delivery confirmed automatically');
            return $order;
        }

        $order->forceFill([
            'status'                => 'delivered',
            'received_confirmed_at' => now(),
            'delivery_confirmed_by' => $by,
        ])->save();

        return $order;
    }

    /** Scheduler: shipped orders the customer never confirmed. */
    public function autoConfirm(): int
    {
        $s = OrderSetting::current();
        if (!$s->auto_confirm_enabled || $s->auto_confirm_days < 1) {
            return 0;
        }
        $cutoff = now()->subDays($s->auto_confirm_days);
        $done   = 0;

        Order::query()
            ->where('status', 'shipped')
            ->whereNull('received_confirmed_at')
            ->whereNotNull('shipped_at')
            ->where('shipped_at', '<=', $cutoff)
            ->orderBy('shipped_at')
            ->limit(500)
            ->get()
            ->each(function (Order $order) use (&$done) {
                try {
                    $this->confirmReceived($order, 'auto');
                    try {
                        app(OrderNotificationService::class)->notifyOrderStatusUpdate($order, 'delivered');
                    } catch (\Throwable $e) {
                        // push is best-effort
                    }
                    $done++;
                } catch (\Throwable $e) {
                    Log::error('orders:auto-confirm failed for ' . $order->id . ': ' . $e->getMessage());
                }
            });

        // Orders an admin marked delivered: stamp them as confirmed after the same wait.
        Order::query()
            ->where('status', 'delivered')
            ->whereNull('received_confirmed_at')
            ->where('delivered_at', '<=', $cutoff)
            ->update(['received_confirmed_at' => now()]);

        return $done;
    }

    // ── Read model for the app ──────────────────────────────────────────────

    public function tracking(Order $order): array
    {
        $history = OrderStatusHistory::where('order_id', $order->id)->orderBy('created_at')->orderBy('id')->get();
        $s       = OrderSetting::current();

        $reached = fn (string $step) => $history->firstWhere('status', $step)?->created_at;
        $stamps  = [
            'pending'    => $reached('pending') ?? $order->order_date ?? $order->created_at,
            'processing' => $reached('processing'),
            'shipped'    => $order->shipped_at ?? $reached('shipped'),
            'delivered'  => $order->delivered_at ?? $reached('delivered'),
        ];
        $currentIdx = array_search($order->status, self::STEPS, true);

        $steps = [];
        foreach (self::STEPS as $i => $step) {
            $at = $stamps[$step];
            $steps[] = [
                'status' => $step,
                'label'  => self::LABELS[$step],
                'done'   => $order->status !== 'cancelled' && $currentIdx !== false && $i <= $currentIdx,
                'at'     => $at ? Carbon::parse($at)->toIso8601String() : null,
            ];
        }

        $autoAt = null;
        if ($order->status === 'shipped' && $s->auto_confirm_enabled && $order->shipped_at && !$order->received_confirmed_at) {
            $autoAt = Carbon::parse($order->shipped_at)->addDays($s->auto_confirm_days)->toIso8601String();
        }

        return [
            'order_id'              => $order->id,
            'status'                => $order->status,
            'status_label'          => self::LABELS[$order->status] ?? ucfirst((string) $order->status),
            'cancelled'             => $order->status === 'cancelled',
            'steps'                 => $steps,
            'history'               => $history->map(fn ($h) => [
                'status' => $h->status,
                'label'  => self::LABELS[$h->status] ?? ucfirst($h->status),
                'by'     => $h->actor_type,
                'note'   => $h->note,
                'at'     => $h->created_at?->toIso8601String(),
            ])->values(),
            'can_confirm_receipt'   => $this->canConfirm($order),
            'received_confirmed_at' => $order->received_confirmed_at ? Carbon::parse($order->received_confirmed_at)->toIso8601String() : null,
            'delivery_confirmed_by' => $order->delivery_confirmed_by,
            'auto_confirm_at'       => $autoAt,
            'auto_confirm_days'     => $s->auto_confirm_enabled ? $s->auto_confirm_days : null,
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function actor(Order $order): array
    {
        $user = Auth::user();
        if (!$user) {
            return ['system', null];
        }
        if ((string) $user->id === (string) $order->user_id) {
            return ['customer', $user->id];
        }
        return ['admin', $user->id];
    }

    private function history(Order $order, ?string $from, string $to, string $actorType, $actorId, ?string $note = null): void
    {
        try {
            OrderStatusHistory::create([
                'order_id'    => (string) $order->id,
                'from_status' => $from,
                'status'      => $to,
                'actor_type'  => $actorType,
                'actor_id'    => $actorId,
                'note'        => $note,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Order history not saved for ' . $order->id . ': ' . $e->getMessage());
        }
    }

    /** In-app notification row: shows in the app's notification centre even without a push token. */
    private function inApp(Order $order, string $type, string $title, string $body, array $extra = []): void
    {
        if (!$order->user_id) {
            return;
        }
        try {
            UserNotification::create([
                'user_id'         => $order->user_id,
                'type'            => $type,
                'title'           => $title,
                'body'            => $body,
                'data'            => array_merge([
                    'type'           => $type,
                    'order_id'       => (string) $order->id,
                    'invoice_number' => (string) ($order->invoice_number ?? ''),
                    'status'         => $order->status,
                    'route'          => '/orders/' . $order->id,
                ], $extra),
                'sent_via'        => 'in_app',
                'delivery_status' => UserNotification::STATUS_SENT,
            ]);
        } catch (\Throwable $e) {
            Log::warning('In-app notification not saved for order ' . $order->id . ': ' . $e->getMessage());
        }
    }

    private function ref(Order $order): string
    {
        return (string) ($order->invoice_number ?: substr((string) $order->id, 0, 8));
    }
}
