<?php

namespace App\Services\Dispatch;

use App\Models\Dispatch\DeliveryZone;
use App\Models\Dispatch\DispatchEvent;
use App\Models\Dispatch\DispatchSetting;
use App\Models\Dispatch\DispatchBatch;
use App\Models\Dispatch\OrderParcel;
use App\Models\Order;
use App\Models\StockLocation;
use App\Services\OrderNotificationService;
use App\Services\OrderTrackingService;
use App\Support\DeliveryInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Packing → labelling → batching → loading → delivery.
 *
 *   1. Packer records the parcels of an order (bags / cartons + handling tags),
 *      picked quantities and substitutions                       → packing_status = packed
 *   2. A second person checks it                                → packing_status = verified
 *   3. Dispatcher groups verified orders into a batch (one rider, one route).
 *      Stops are ordered by the zone's area list.               → dispatch_status = assigned
 *   4. Every parcel is scanned onto the vehicle; a parcel from another batch
 *      is rejected on the spot.                                  → parcel.status = loaded
 *   5. Dispatch: orders become "shipped" (customer is notified)  → out_for_delivery
 *   6. Each stop is marked delivered / failed. Completing the run sends
 *      failed orders back to the queue for the next batch.
 */
class DispatchService
{
    /** Characters used in codes — no 0/O/1/I so they can be read out over the phone. */
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const ACTIVE_ORDER_STATUSES = ['pending', 'processing', 'shipped'];

    // ── Packing ──────────────────────────────────────────────────────────────

    /**
     * Save what the packer did.
     *
     * $data = [
     *   'stock_location_id' => ?int,
     *   'delivery_zone_id'  => ?int,          // null → auto-match from the address
     *   'packing_notes'     => ?string,
     *   'items'   => [itemId => ['picked_quantity' => int|null, 'substitution' => ?string]],
     *   'parcels' => [['package_type' => 'bag', 'handling' => ['fragile'], 'weight_kg' => ?float, 'contents' => ?string], ...],
     * ]
     */
    public function savePacking(Order $order, array $data, int $userId): Order
    {
        $this->assertNotOnTheRoad($order);

        $parcels = array_values(array_filter($data['parcels'] ?? [], fn ($p) => is_array($p)));
        if (count($parcels) < 1) {
            throw new RuntimeException('Add at least one parcel (bag or carton).');
        }
        if (count($parcels) > 99) {
            throw new RuntimeException('An order cannot have more than 99 parcels.');
        }

        return DB::transaction(function () use ($order, $data, $parcels, $userId) {
            $order = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            foreach ($data['items'] ?? [] as $itemId => $row) {
                $item = $order->items()->whereKey($itemId)->first();
                if (!$item) {
                    continue;
                }
                $picked = $row['picked_quantity'] ?? null;
                $item->update([
                    'picked_quantity' => ($picked === '' || $picked === null) ? null : max(0, (int) $picked),
                    'substitution'    => trim((string) ($row['substitution'] ?? '')) ?: null,
                ]);
            }

            $this->syncParcels($order, $parcels);

            $order->fill([
                'stock_location_id' => $data['stock_location_id'] ?? $order->stock_location_id ?? StockLocation::getDefault()?->id,
                'packing_notes'     => trim((string) ($data['packing_notes'] ?? '')) ?: null,
                'package_count'     => count($parcels),
                'packing_status'    => 'packed',
                'packed_by'         => $userId,
                'packed_at'         => now(),
                // Any change to the packing needs a fresh check.
                'checked_by'        => null,
                'checked_at'        => null,
            ]);

            $zoneId = $data['delivery_zone_id'] ?? null;
            $order->delivery_zone_id = $zoneId ?: ($this->matchZone($order)?->id ?? $order->delivery_zone_id);

            if ($order->status === 'pending') {
                $order->status = 'processing';
            }
            $order->save();

            return $order->fresh(['parcels', 'items']);
        });
    }

    /** Keep existing parcel codes (labels already printed stay valid); add / drop the rest. */
    private function syncParcels(Order $order, array $parcels): void
    {
        $existing = $order->parcels()->get()->keyBy('sequence');
        $base     = $this->parcelBase($order, $existing);

        foreach ($parcels as $i => $p) {
            $seq      = $i + 1;
            $handling = array_values(array_intersect(array_keys(OrderParcel::HANDLING), (array) ($p['handling'] ?? [])));
            $type     = array_key_exists($p['package_type'] ?? '', OrderParcel::TYPES) ? $p['package_type'] : 'bag';
            $weight   = isset($p['weight_kg']) && $p['weight_kg'] !== '' ? round((float) $p['weight_kg'], 2) : null;

            $attrs = [
                'package_type' => $type,
                'handling'     => $handling,
                'weight_kg'    => $weight,
                'contents'     => trim((string) ($p['contents'] ?? '')) ?: null,
            ];

            if ($parcel = $existing->get($seq)) {
                $parcel->update($attrs);
            } else {
                OrderParcel::create($attrs + [
                    'order_id' => $order->id,
                    'sequence' => $seq,
                    'code'     => "{$base}-{$seq}",
                    'status'   => 'packed',
                ]);
            }
        }

        $order->parcels()->where('sequence', '>', count($parcels))->delete();
    }

    /** All parcels of one order share a base code: GZ7K3M9Q-1, GZ7K3M9Q-2 … */
    private function parcelBase(Order $order, Collection $existing): string
    {
        if ($first = $existing->first()) {
            return Str::beforeLast($first->code, '-');
        }
        do {
            $base = 'GZ' . $this->randomCode(6);
        } while (OrderParcel::where('code', 'like', $base . '-%')->exists());

        return $base;
    }

    public function verifyPacking(Order $order, int $userId, bool $allowSelfCheck = false): Order
    {
        $this->assertNotOnTheRoad($order);

        if (!in_array($order->packing_status, ['packed', 'verified'], true) || $order->parcels()->count() === 0) {
            throw new RuntimeException('Record the parcels before verifying.');
        }
        if ((int) $order->packed_by === $userId && !$allowSelfCheck) {
            throw new RuntimeException('Packing should be checked by a different staff member. Tick "I checked it myself" to override.');
        }

        $order->update([
            'packing_status' => 'verified',
            'checked_by'     => $userId,
            'checked_at'     => now(),
        ]);
        $order->parcels()->whereNull('labelled_at')->update(['labelled_at' => now()]);

        return $order;
    }

    public function markLabelsPrinted(Order $order): void
    {
        $order->parcels()->whereNull('labelled_at')->update(['labelled_at' => now()]);
    }

    // ── Zones ────────────────────────────────────────────────────────────────

    /** Best delivery zone for an order's address (LGA / city exact match beats a street keyword). */
    public function matchZone(Order $order): ?DeliveryZone
    {
        $addr = $order->shippingAddress ?? $order->billingAddress;
        if (!$addr) {
            return null;
        }

        $exact = collect([$addr->lga ?? null, $addr->city ?? null])->filter()->map(fn ($v) => mb_strtolower(trim($v)))->all();
        $text  = mb_strtolower(implode(' ', array_filter([$addr->street, $addr->landmark ?? null, $addr->city, $addr->lga ?? null])));

        $zones = DeliveryZone::active()
            ->when($order->stock_location_id, fn ($q) => $q->where(fn ($w) => $w->where('stock_location_id', $order->stock_location_id)->orWhereNull('stock_location_id')))
            ->get();

        $best = null;
        $bestScore = 0;
        foreach ($zones as $zone) {
            $score = 0;
            foreach ($zone->keywords() as $kw) {
                if (in_array($kw, $exact, true)) {
                    $score = max($score, 3);
                } elseif (mb_strlen($kw) >= 3 && preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $text)) {
                    $score = max($score, 2);
                }
            }
            if ($score > $bestScore) {
                $best = $zone;
                $bestScore = $score;
            }
        }

        return $best;
    }

    // ── Batches ──────────────────────────────────────────────────────────────

    /** Orders that are ready to go out but not on any run yet, grouped by zone id (0 = no zone). */
    public function readyQueue(?int $branchId = null): Collection
    {
        return Order::with(['shippingAddress', 'billingAddress', 'user', 'customer', 'deliveryZone', 'parcels'])
            ->whereNull('dispatch_batch_id')
            ->whereIn('status', ['pending', 'processing'])
            ->whereIn('packing_status', ['packed', 'verified'])
            ->where(fn ($q) => $q->whereNull('dispatch_status')->orWhere('dispatch_status', '!=', 'courier'))
            ->when($branchId, fn ($q) => $q->where('stock_location_id', $branchId))
            ->orderByRaw('delivery_date IS NULL, delivery_date')
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($o) => (int) $o->delivery_zone_id);
    }

    public function createBatch(array $orderIds, array $attrs, int $userId): DispatchBatch
    {
        $orderIds = array_values(array_unique(array_filter($orderIds)));
        if (!$orderIds) {
            throw new RuntimeException('Pick at least one order for the batch.');
        }

        return DB::transaction(function () use ($orderIds, $attrs, $userId) {
            $orders = $this->lockAssignable($orderIds);

            $zone   = !empty($attrs['delivery_zone_id']) ? DeliveryZone::find($attrs['delivery_zone_id']) : null;
            $zone ??= $this->dominantZone($orders);
            $branch = $attrs['stock_location_id'] ?? $orders->pluck('stock_location_id')->filter()->first() ?? StockLocation::getDefault()?->id;

            $batch = DispatchBatch::create([
                'code'                 => $this->nextBatchCode(),
                'route_code'           => $this->nextRouteCode($zone),
                'stock_location_id'    => $branch,
                'delivery_zone_id'     => $zone?->id,
                'rider_id'             => $attrs['rider_id'] ?? null,
                'status'               => 'draft',
                'planned_departure_at' => $attrs['planned_departure_at'] ?? null,
                'notes'                => $attrs['notes'] ?? null,
                'created_by'           => $userId,
            ]);

            $this->attachOrders($batch, $orders);
            $this->autoSequence($batch);

            return $batch;
        });
    }

    public function addOrders(DispatchBatch $batch, array $orderIds): void
    {
        $this->assertEditable($batch);
        DB::transaction(function () use ($batch, $orderIds) {
            $this->attachOrders($batch, $this->lockAssignable($orderIds), (int) $batch->orders()->max('stop_sequence'));
        });
    }

    public function removeOrder(DispatchBatch $batch, Order $order): void
    {
        $this->assertEditable($batch);
        if ((int) $order->dispatch_batch_id !== $batch->id) {
            throw new RuntimeException('That order is not on this batch.');
        }
        DB::transaction(function () use ($batch, $order) {
            $this->releaseOrder($order, null);
            $this->renumber($batch);
        });
    }

    /** Move a stop up / down the route. */
    public function moveStop(DispatchBatch $batch, Order $order, string $direction): void
    {
        $this->assertEditable($batch);
        $orders = $batch->orders()->get()->values();
        $idx = $orders->search(fn ($o) => $o->id === $order->id);
        if ($idx === false) {
            throw new RuntimeException('That order is not on this batch.');
        }
        $swap = $direction === 'up' ? $idx - 1 : $idx + 1;
        if ($swap < 0 || $swap >= $orders->count()) {
            return;
        }
        $list = $orders->all();
        [$list[$idx], $list[$swap]] = [$list[$swap], $list[$idx]];
        foreach ($list as $i => $o) {
            $o->updateQuietly(['stop_sequence' => $i + 1]);
        }
    }

    /** Order stops by the position of their area in the zone's area list (dispatcher lists areas in driving order). */
    public function autoSequence(DispatchBatch $batch): void
    {
        $areas  = $batch->zone ? array_map(fn ($a) => mb_strtolower(trim($a)), $batch->zone->areas ?? []) : [];
        $orders = $batch->orders()->with(['shippingAddress', 'billingAddress'])->get();

        $rank = function (Order $o) use ($areas) {
            $a = $o->shippingAddress ?? $o->billingAddress;
            if (!$a || !$areas) {
                return [PHP_INT_MAX, mb_strtolower(($a->lga ?? '') . ' ' . ($a->city ?? '') . ' ' . ($a->street ?? ''))];
            }
            $text = mb_strtolower(implode(' ', array_filter([$a->lga ?? null, $a->city, $a->street, $a->landmark ?? null])));
            foreach ($areas as $i => $kw) {
                if ($kw !== '' && str_contains($text, $kw)) {
                    return [$i, mb_strtolower((string) $a->street)];
                }
            }
            return [PHP_INT_MAX - 1, mb_strtolower((string) $a->street)];
        };

        // Orders already past their promised time go first; the rest follow the road.
        $late = fn (Order $o) => $o->promisedAt()?->isPast() ? 0 : 1;
        $sorted = $orders->sort(function ($x, $y) use ($rank, $late) {
            return [$late($x), ...$rank($x)] <=> [$late($y), ...$rank($y)];
        })->values();

        foreach ($sorted as $i => $o) {
            $o->updateQuietly(['stop_sequence' => $i + 1]);
        }
    }

    public function updateBatch(DispatchBatch $batch, array $attrs): void
    {
        if (in_array($batch->status, ['completed', 'cancelled'], true)) {
            throw new RuntimeException('This batch is closed.');
        }
        $batch->update(array_intersect_key($attrs, array_flip(['rider_id', 'planned_departure_at', 'notes'])));
    }

    // ── Loading ──────────────────────────────────────────────────────────────

    /**
     * Scan a parcel onto a batch's vehicle.
     * Returns ['ok' => bool, 'level' => success|info|danger, 'message' => string, 'parcel' => ?OrderParcel].
     */
    public function scanParcel(DispatchBatch $batch, string $raw, int $userId): array
    {
        if (!$batch->isEditable()) {
            return ['ok' => false, 'level' => 'danger', 'message' => 'This batch has already left / is closed.', 'parcel' => null];
        }

        $code = $this->parseScan($raw);
        $parcel = OrderParcel::with('order', 'batch')->where('code', $code)->first();

        if (!$parcel) {
            return ['ok' => false, 'level' => 'danger', 'message' => "Unknown parcel code “{$code}”.", 'parcel' => null];
        }

        $total = $parcel->order?->package_count ?: $parcel->order?->parcels()->count();
        $what  = "Order {$parcel->order?->ref} · parcel {$parcel->sequence} of {$total}";

        if ((int) $parcel->dispatch_batch_id !== $batch->id) {
            $where = $parcel->batch
                ? "it belongs to batch {$parcel->batch->code} (route {$parcel->batch->route_code})"
                : 'it is not assigned to any batch yet';
            return ['ok' => false, 'level' => 'danger', 'message' => "WRONG BATCH — {$what}: {$where}. Keep it off this vehicle.", 'parcel' => $parcel];
        }

        if ($parcel->status === 'loaded') {
            return ['ok' => true, 'level' => 'info', 'message' => "Already loaded — {$what}.", 'parcel' => $parcel];
        }

        DB::transaction(function () use ($parcel, $batch, $userId) {
            $parcel->update(['status' => 'loaded', 'loaded_at' => now(), 'loaded_by' => $userId]);
            if ($batch->status === 'draft') {
                $batch->update(['status' => 'loading']);
            }
        });

        $stop = $parcel->order?->stop_sequence;
        return ['ok' => true, 'level' => 'success', 'message' => "Loaded — {$what}" . ($stop ? " (stop {$stop})" : '') . '.', 'parcel' => $parcel];
    }

    public function unloadParcel(DispatchBatch $batch, OrderParcel $parcel): void
    {
        $this->assertEditable($batch);
        if ((int) $parcel->dispatch_batch_id === $batch->id) {
            $parcel->update(['status' => 'packed', 'loaded_at' => null, 'loaded_by' => null]);
        }
    }

    /** Accepts a raw code, a QR payload "GOZAK|<code>|…" or a URL ending in the code. */
    public function parseScan(string $raw): string
    {
        $raw = trim($raw);
        if (str_contains($raw, '|')) {
            $parts = explode('|', $raw);
            $raw = $parts[1] ?? $parts[0];
        } elseif (str_contains($raw, '/')) {
            $raw = Str::afterLast(rtrim($raw, '/'), '/');
        }
        return strtoupper(trim($raw));
    }

    // ── Dispatch & delivery ──────────────────────────────────────────────────

    public function dispatch(DispatchBatch $batch, int $userId, bool $force = false): void
    {
        $this->assertEditable($batch);

        if (!$batch->rider_id) {
            throw new RuntimeException('Assign a rider before dispatching.');
        }
        $orders = $batch->orders()->get();
        if ($orders->isEmpty()) {
            throw new RuntimeException('The batch has no orders.');
        }
        $missing = $batch->parcels()->where('status', '!=', 'loaded')->count();
        if ($missing && !$force) {
            throw new RuntimeException("{$missing} parcel(s) have not been scanned onto the vehicle.");
        }

        $useCodes = DispatchSetting::current()->require_delivery_code;

        DB::transaction(function () use ($batch, $orders, $userId, $useCodes) {
            $batch->update([
                'status'        => 'dispatched',
                'dispatched_at' => now(),
                'dispatched_by' => $userId,
                'cash_expected' => $orders->sum(fn ($o) => DeliveryInfo::amountToCollect($o)),
            ]);
            $batch->parcels()->update(['status' => 'out_for_delivery']);
            foreach ($orders as $order) {
                $order->dispatch_status = 'out_for_delivery';
                $order->out_for_delivery_at = now();
                $order->delivery_code = $useCodes ? str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT) : null;
                $order->delivery_code_attempts = 0;
                $order->status = 'shipped';
                $order->save();
            }
            DispatchEvent::log('dispatched', null, $batch, ['note' => $orders->count() . ' stops']);
        });

        $batch->load('rider');
        foreach ($orders as $order) {
            $this->notifyOutForDelivery($order->fresh(), $batch);
        }
    }

    /** Tell the customer who is coming and (if enabled) the code to give the rider. */
    private function notifyOutForDelivery(Order $order, DispatchBatch $batch): void
    {
        $rider = $batch->rider;
        $who   = $rider ? "{$rider->name}" . ($rider->phone ? " ({$rider->phone})" : '') : 'our rider';
        $title = '🚚 Your order is on its way';
        $body  = "Order #{$order->ref} is out for delivery with {$who}.";
        if ($order->delivery_code) {
            $body .= " Your delivery code is {$order->delivery_code} — give it to the rider only when you have all your items.";
        }

        try {
            app(OrderTrackingService::class)->notifyCustomer($order, 'order_delivery', $title, $body, [
                'delivery_code' => $order->delivery_code,
                'rider_name'    => $rider?->name,
                'rider_phone'   => $rider?->phone,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Dispatch: in-app delivery notice failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
        try {
            app(OrderNotificationService::class)->notifyCustom($order, $title, $body, [
                'type'          => 'order_delivery',
                'delivery_code' => (string) $order->delivery_code,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Dispatch: delivery push failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /** For the customer app's tracking screen. */
    public function customerDeliveryInfo(Order $order): ?array
    {
        if ($order->dispatch_status !== 'out_for_delivery' || !$order->dispatch_batch_id) {
            return null;
        }
        $batch = $order->dispatchBatch()->with('rider')->first();
        $ahead = $batch ? $batch->orders()->where('dispatch_status', 'out_for_delivery')->where('stop_sequence', '<', (int) $order->stop_sequence)->count() : null;

        return [
            'delivery_code' => $order->delivery_code,
            'rider_name'    => $batch?->rider?->name,
            'rider_phone'   => $batch?->rider?->phone,
            'vehicle'       => $batch?->rider?->vehicleLabel(),
            'stops_before_you' => $ahead,
            'out_since'     => $order->out_for_delivery_at?->toIso8601String(),
            'parcels'       => (int) ($order->package_count ?: $order->parcels()->count()),
        ];
    }

    /** Admin / supervisor records a stop from the batch page. $outcome: delivered | failed */
    public function recordStop(DispatchBatch $batch, Order $order, string $outcome, ?string $note = null, array $cash = []): void
    {
        if ($outcome === 'delivered') {
            $this->confirmDelivery($batch, $order, [
                'method'         => 'admin',
                'note'           => $note,
                'cash_collected' => $cash['cash_collected'] ?? null,
                'cash_method'    => $cash['cash_method'] ?? null,
            ]);
        } else {
            $this->failDelivery($batch, $order, $note ?: 'Delivery attempt failed');
        }
    }

    /**
     * Mark a stop delivered.
     * $o = [method => code|photo|admin|none, receiver, proof, lat, lng, cash_collected, cash_method, note]
     */
    public function confirmDelivery(DispatchBatch $batch, Order $order, array $o): void
    {
        $this->assertOpenStop($batch, $order);

        $due  = DeliveryInfo::amountToCollect($order);
        $cash = array_key_exists('cash_collected', $o) && $o['cash_collected'] !== null && $o['cash_collected'] !== ''
            ? round((float) $o['cash_collected'], 2)
            : ($due > 0 ? $due : null);
        $method = $cash ? (in_array($o['cash_method'] ?? null, ['cash', 'transfer', 'pos'], true) ? $o['cash_method'] : 'cash') : null;

        DB::transaction(function () use ($batch, $order, $o, $due, $cash, $method) {
            $order->parcels()->update(['status' => 'delivered', 'delivered_at' => now()]);
            $order->fill([
                'dispatch_status'   => 'delivered',
                'dispatch_note'     => $o['note'] ?? null,
                'delivery_method'   => $o['method'] ?? 'admin',
                'delivery_receiver' => $o['receiver'] ?? null,
                'delivery_proof'    => $o['proof'] ?? $order->delivery_proof,
                'delivered_lat'     => $o['lat'] ?? null,
                'delivered_lng'     => $o['lng'] ?? null,
                'cash_collected'    => $cash,
                'cash_method'       => $method,
                'status'            => 'delivered',
            ]);
            // A correct delivery code is the customer confirming receipt.
            if (($o['method'] ?? null) === 'code') {
                $order->delivery_confirmed_by = 'customer';
                $order->received_confirmed_at = now();
            }
            // Pay-on-delivery settled in full.
            if ($due > 0 && $cash !== null && $cash + 0.01 >= $due) {
                $order->payment_status = 'paid';
                $order->paid_at = now();
            }
            $order->save();

            DispatchEvent::log('delivered', $order, $batch, [
                'note' => trim(($o['method'] ?? 'admin') . ($cash ? " · collected ₦" . number_format($cash, 2) . " ({$method})" : '') . (!empty($o['note']) ? ' · ' . $o['note'] : '')),
                'lat'  => $o['lat'] ?? null,
                'lng'  => $o['lng'] ?? null,
            ]);
        });

        $this->notify($order, 'delivered');
    }

    public function failDelivery(DispatchBatch $batch, Order $order, string $reason, ?float $lat = null, ?float $lng = null): void
    {
        $this->assertOpenStop($batch, $order);

        $order->dispatch_status = 'failed';
        $order->dispatch_note = $reason;
        $order->delivery_attempts = (int) $order->delivery_attempts + 1;
        $order->save();

        DispatchEvent::log('failed', $order, $batch, ['note' => $reason, 'lat' => $lat, 'lng' => $lng]);
    }

    private function assertOpenStop(DispatchBatch $batch, Order $order): void
    {
        if ($batch->status !== 'dispatched') {
            throw new RuntimeException('Deliveries can only be recorded while the batch is out.');
        }
        if ((int) $order->dispatch_batch_id !== $batch->id) {
            throw new RuntimeException('That order is not on this batch.');
        }
        if ($order->dispatch_status !== 'out_for_delivery') {
            throw new RuntimeException("Stop for order {$order->ref} is already closed ({$order->dispatch_status}).");
        }
    }

    /** Close the run. Failed stops go back to the queue with their parcels. */
    public function complete(DispatchBatch $batch, ?float $cashReceived = null, ?string $cashNote = null, ?int $userId = null): int
    {
        if ($batch->status !== 'dispatched') {
            throw new RuntimeException('Only a batch that is out for delivery can be completed.');
        }
        $open = $batch->orders()->where('dispatch_status', 'out_for_delivery')->count();
        if ($open) {
            throw new RuntimeException("{$open} stop(s) are still open — mark them delivered or failed first.");
        }

        return DB::transaction(function () use ($batch, $cashReceived, $cashNote, $userId) {
            $failed = $batch->orders()->where('dispatch_status', 'failed')->get();
            foreach ($failed as $order) {
                $this->releaseOrder($order, "Returned from {$batch->code}: " . ($order->dispatch_note ?: 'not delivered'), 'processing');
            }
            $inHand = $batch->cashCollectedInHand();
            $batch->update([
                'status'             => 'completed',
                'completed_at'       => now(),
                'cash_received'      => $cashReceived ?? ($inHand > 0 ? null : 0),
                'cash_received_by'   => $cashReceived !== null ? $userId : null,
                'cash_reconciled_at' => $cashReceived !== null ? now() : null,
                'cash_note'          => $cashNote,
            ]);
            DispatchEvent::log('completed', null, $batch, ['note' => $failed->count() . ' returned']);
            if ($cashReceived !== null) {
                DispatchEvent::log('cash_in', null, $batch, ['note' => '₦' . number_format($cashReceived, 2) . ' handed in (rider reported ₦' . number_format($inHand, 2) . ' cash)']);
            }
            return $failed->count();
        });
    }

    /** Record cash handed in for a run that was closed without it. */
    public function reconcileCash(DispatchBatch $batch, float $amount, ?string $note, int $userId): void
    {
        if ($batch->status !== 'completed') {
            throw new RuntimeException('Close the run first.');
        }
        $batch->update([
            'cash_received'      => round($amount, 2),
            'cash_received_by'   => $userId,
            'cash_reconciled_at' => now(),
            'cash_note'          => $note,
        ]);
        DispatchEvent::log('cash_in', null, $batch, ['note' => '₦' . number_format($amount, 2) . ' handed in']);
    }

    public function cancel(DispatchBatch $batch): void
    {
        $this->assertEditable($batch);
        DB::transaction(function () use ($batch) {
            foreach ($batch->orders()->get() as $order) {
                $this->releaseOrder($order, null);
            }
            $batch->update(['status' => 'cancelled']);
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function lockAssignable(array $orderIds): Collection
    {
        $orders = Order::whereIn('id', $orderIds)->lockForUpdate()->get();
        if ($orders->count() !== count(array_unique($orderIds))) {
            throw new RuntimeException('Some orders could not be found.');
        }
        foreach ($orders as $o) {
            if ($o->dispatch_batch_id) {
                throw new RuntimeException("Order {$o->ref} is already on another batch.");
            }
            if ($o->dispatch_status === 'courier') {
                throw new RuntimeException("Order {$o->ref} goes with a marketplace courier. Switch it to GozakMart riders on the order page first.");
            }
            if ($o->packing_status !== 'verified') {
                throw new RuntimeException("Order {$o->ref} has not been checked (packing verification) yet.");
            }
            if (!in_array($o->status, ['pending', 'processing'], true)) {
                throw new RuntimeException("Order {$o->ref} is {$o->status} and cannot be dispatched.");
            }
        }
        return $orders;
    }

    private function attachOrders(DispatchBatch $batch, Collection $orders, int $startAt = 0): void
    {
        foreach ($orders->values() as $i => $order) {
            $order->updateQuietly([
                'dispatch_batch_id' => $batch->id,
                'dispatch_status'   => 'assigned',
                'stop_sequence'     => $startAt + $i + 1,
                'dispatch_note'     => null,
            ]);
            $order->parcels()->update(['dispatch_batch_id' => $batch->id, 'status' => 'packed', 'loaded_at' => null, 'loaded_by' => null]);
            DispatchEvent::log('assigned', $order, $batch, ['note' => "{$batch->code} stop " . ($startAt + $i + 1)]);
        }
    }

    private function releaseOrder(Order $order, ?string $note, ?string $status = null): void
    {
        $order->parcels()->update(['dispatch_batch_id' => null, 'status' => 'packed', 'loaded_at' => null, 'loaded_by' => null]);
        $order->dispatch_batch_id = null;
        $order->stop_sequence = null;
        $order->delivery_code = null;
        $order->delivery_code_attempts = 0;
        $order->dispatch_status = $note ? 'returned' : null;
        $order->dispatch_note = $note;
        if ($status) {
            $order->status = $status;
        }
        $order->save();
        if ($note) {
            DispatchEvent::log('returned', $order, null, ['note' => $note]);
        }
    }

    private function renumber(DispatchBatch $batch): void
    {
        foreach ($batch->orders()->get()->values() as $i => $o) {
            $o->updateQuietly(['stop_sequence' => $i + 1]);
        }
    }

    private function dominantZone(Collection $orders): ?DeliveryZone
    {
        $id = $orders->pluck('delivery_zone_id')->filter()->countBy()->sortDesc()->keys()->first();
        return $id ? DeliveryZone::find($id) : null;
    }

    private function nextBatchCode(): string
    {
        $prefix = 'DSP-' . now()->format('ymd') . '-';
        $last = DispatchBatch::where('code', 'like', $prefix . '%')->lockForUpdate()->orderByDesc('code')->value('code');
        $n = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;
        return $prefix . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    /** Route code: zone code + today's run number for that zone, e.g. IKJ-02. */
    private function nextRouteCode(?DeliveryZone $zone): string
    {
        $prefix = $zone ? strtoupper($zone->code) : 'GEN';
        $n = DispatchBatch::where('route_code', 'like', $prefix . '-%')
            ->whereDate('created_at', now()->toDateString())
            ->count() + 1;
        return $prefix . '-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    }

    private function randomCode(int $len): string
    {
        $out = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < $len; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }
        return $out;
    }

    private function assertEditable(DispatchBatch $batch): void
    {
        if (!$batch->isEditable()) {
            throw new RuntimeException("Batch {$batch->code} is {$batch->statusLabel()} and can no longer be changed.");
        }
    }

    private function assertNotOnTheRoad(Order $order): void
    {
        if ($order->dispatch_batch_id && in_array($order->dispatch_status, ['out_for_delivery', 'delivered'], true)) {
            throw new RuntimeException('This order is already out for delivery — its packing can no longer be changed.');
        }
        if ($order->dispatch_batch_id) {
            throw new RuntimeException('Remove this order from its dispatch batch before changing the packing.');
        }
    }

    private function notify(Order $order, string $status): void
    {
        try {
            app(OrderNotificationService::class)->notifyOrderStatusUpdate($order->loadMissing('user'), $status);
        } catch (\Throwable $e) {
            Log::warning('Dispatch: push notification failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
