<?php

namespace App\Services\Dispatch;

use App\Models\Dispatch\DeliveryRider;
use App\Models\Dispatch\DispatchBatch;
use App\Models\Dispatch\DispatchEvent;
use App\Models\Dispatch\DispatchSetting;
use App\Models\Order;
use App\Support\DeliveryInfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Everything the rider does on the road — shared by the rider web app (/rider)
 * and the JSON API (/api/rider) so a Flutter rider app behaves the same.
 */
class RiderDeliveryService
{
    public function __construct(private DispatchService $dispatch)
    {
    }

    public function riderFor(?int $userId): DeliveryRider
    {
        $rider = DeliveryRider::forUser($userId);
        if (!$rider || !$rider->is_active) {
            throw new RuntimeException('Your account is not linked to an active rider profile. Ask the dispatch supervisor.');
        }
        return $rider;
    }

    /** Runs to show: anything not closed, plus runs closed today. */
    public function runs(DeliveryRider $rider): Collection
    {
        return DispatchBatch::with('zone')
            ->withCount(['orders', 'parcels', 'orders as delivered_count' => fn ($q) => $q->where('dispatch_status', 'delivered')])
            ->where('rider_id', $rider->id)
            ->where(fn ($q) => $q->whereIn('status', ['draft', 'loading', 'dispatched'])
                ->orWhere(fn ($w) => $w->where('status', 'completed')->where('completed_at', '>=', now()->startOfDay())))
            ->orderByRaw("CASE status WHEN 'dispatched' THEN 0 WHEN 'loading' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")
            ->latest('id')
            ->get();
    }

    public function run(DeliveryRider $rider, int $batchId): DispatchBatch
    {
        $batch = DispatchBatch::with(['zone', 'branch', 'orders' => fn ($q) => $q->with(['parcels', 'shippingAddress', 'billingAddress', 'user', 'customer'])])
            ->where('rider_id', $rider->id)
            ->find($batchId);
        if (!$batch) {
            throw new RuntimeException('That run is not assigned to you.');
        }
        return $batch;
    }

    private function stop(DispatchBatch $batch, string $orderId): Order
    {
        $order = $batch->orders->firstWhere('id', $orderId) ?? Order::where('dispatch_batch_id', $batch->id)->find($orderId);
        if (!$order) {
            throw new RuntimeException('That stop is not on your run.');
        }
        return $order;
    }

    /**
     * Confirm a delivery.
     * $in = [code, receiver, cash_collected, cash_method, note, lat, lng]
     */
    public function deliver(DeliveryRider $rider, int $batchId, string $orderId, array $in, ?UploadedFile $photo = null): Order
    {
        $batch = $this->run($rider, $batchId);
        $order = $this->stop($batch, $orderId);
        $s     = DispatchSetting::current();

        if ($order->dispatch_status !== 'out_for_delivery') {
            throw new RuntimeException("This stop is already closed ({$order->dispatch_status}).");
        }

        $code   = preg_replace('/\D/', '', (string) ($in['code'] ?? ''));
        $method = 'none';

        if ($s->require_delivery_code && $order->delivery_code) {
            if ($code !== '') {
                if ($order->delivery_code_attempts >= $s->max_code_attempts) {
                    throw new RuntimeException('Too many wrong codes for this order.' . ($s->allow_photo_fallback ? ' Take a photo of the hand-over instead, or call dispatch.' : ' Call dispatch.'));
                }
                if (!hash_equals((string) $order->delivery_code, $code)) {
                    $order->increment('delivery_code_attempts');
                    DispatchEvent::log('code_failed', $order, $batch, ['note' => 'Wrong delivery code entered', 'lat' => $in['lat'] ?? null, 'lng' => $in['lng'] ?? null]);
                    $left = max(0, $s->max_code_attempts - $order->delivery_code_attempts);
                    throw new RuntimeException("Wrong delivery code. {$left} attempt(s) left. Ask the customer to check the code in their GozakMart app.");
                }
                $method = 'code';
            } elseif ($photo && $s->allow_photo_fallback) {
                $method = 'photo';
            } else {
                throw new RuntimeException('Enter the customer\'s 4-digit delivery code' . ($s->allow_photo_fallback ? ', or take a photo of the hand-over if they can\'t give it.' : '.'));
            }
        } elseif ($photo) {
            $method = 'photo';
        }

        if ($method === 'photo' && trim((string) ($in['receiver'] ?? '')) === '') {
            throw new RuntimeException('Write the name of the person who received the parcels.');
        }

        $proof = $photo ? $photo->store('delivery-proofs/' . now()->format('Y/m'), 'public') : null;

        $this->dispatch->confirmDelivery($batch, $order, [
            'method'         => $method,
            'receiver'       => trim((string) ($in['receiver'] ?? '')) ?: null,
            'proof'          => $proof,
            'lat'            => is_numeric($in['lat'] ?? null) ? (float) $in['lat'] : null,
            'lng'            => is_numeric($in['lng'] ?? null) ? (float) $in['lng'] : null,
            'cash_collected' => DeliveryInfo::amountToCollect($order) > 0 ? ($in['cash_collected'] ?? null) : null,
            'cash_method'    => $in['cash_method'] ?? null,
            'note'           => trim((string) ($in['note'] ?? '')) ?: null,
        ]);

        return $order->fresh();
    }

    public function fail(DeliveryRider $rider, int $batchId, string $orderId, string $reason, ?string $note = null, $lat = null, $lng = null): Order
    {
        $batch = $this->run($rider, $batchId);
        $order = $this->stop($batch, $orderId);
        $text  = trim($reason . ($note ? ' — ' . $note : ''));
        $this->dispatch->failDelivery($batch, $order, $text, is_numeric($lat) ? (float) $lat : null, is_numeric($lng) ? (float) $lng : null);
        return $order->fresh();
    }

    public function logCall(DeliveryRider $rider, int $batchId, string $orderId): void
    {
        $batch = $this->run($rider, $batchId);
        DispatchEvent::log('called', $this->stop($batch, $orderId), $batch);
    }

    /** JSON shape for the API (and handy for debugging). */
    public function stopPayload(Order $o): array
    {
        $a = DeliveryInfo::address($o);
        return [
            'order_id'       => $o->id,
            'order_ref'      => $o->ref,
            'stop'           => $o->stop_sequence,
            'status'         => $o->dispatch_status,
            'note'           => $o->dispatch_note,
            'customer'       => DeliveryInfo::recipient($o),
            'phone'          => DeliveryInfo::phone($o),
            'alt_phone'      => DeliveryInfo::altPhone($o),
            'area'           => DeliveryInfo::area($o),
            'address'        => DeliveryInfo::fullAddress($o),
            'landmark'       => DeliveryInfo::landmark($o),
            'map_url'        => $a ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(DeliveryInfo::fullAddress($o)) : null,
            'amount_to_collect' => DeliveryInfo::amountToCollect($o),
            'promised_at'    => $o->promisedAt()?->toIso8601String(),
            'needs_code'     => DispatchSetting::current()->require_delivery_code && (bool) $o->delivery_code,
            'customer_has_app' => (bool) $o->user_id,
            'parcels'        => $o->parcels->map(fn ($p) => [
                'code'     => $p->code,
                'sequence' => $p->sequence,
                'type'     => $p->typeLabel(),
                'handling' => $p->handlingLabels(),
                'status'   => $p->status,
            ])->values(),
        ];
    }

    public function runPayload(DispatchBatch $b): array
    {
        return [
            'id'          => $b->id,
            'code'        => $b->code,
            'route_code'  => $b->route_code,
            'zone'        => $b->zone?->name,
            'status'      => $b->status,
            'status_label'=> $b->statusLabel(),
            'dispatched_at' => $b->dispatched_at?->toIso8601String(),
            'notes'       => $b->notes,
            'stops'       => $b->relationLoaded('orders') ? $b->orders->map(fn ($o) => $this->stopPayload($o))->values() : null,
            'stops_count' => $b->orders_count ?? $b->orders->count(),
            'delivered_count' => $b->delivered_count ?? ($b->relationLoaded('orders') ? $b->orders->where('dispatch_status', 'delivered')->count() : null),
            'cash_to_collect' => $b->relationLoaded('orders') ? $b->orders->where('dispatch_status', 'out_for_delivery')->sum(fn ($o) => DeliveryInfo::amountToCollect($o)) : null,
        ];
    }
}
