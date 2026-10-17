<?php

namespace App\Services\Logistics;

use App\Models\Address;
use App\Models\Dispatch\DispatchEvent;
use App\Models\Logistics\LogisticsSetting;
use App\Models\Logistics\OrderShipment;
use App\Models\Order;
use App\Models\StockLocation;
use App\Services\OrderPricingService;
use App\Support\DeliveryInfo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GozakMart ⇄ logistics marketplace.
 *
 *   checkout   checkoutOptions(): "GozakMart Delivery" (own riders) + every marketplace provider with price and ETA
 *   order      attach(): the customer's choice is saved on the order (fee in the order, or paid to the rider)
 *   packing    book(): when packing is verified the courier is booked with the chosen provider
 *   updates    handleWebhook() / sync(): picked up → order shipped, delivered → order delivered
 *   own team   with own_via_marketplace on, "GozakMart Delivery" is booked to GozakMart's own team on the marketplace
 *              ("provider": "own"); its staff assign the rider there, and liveTracking() shows that rider in the app
 */
class LogisticsService
{
    public const OWN = 'store';
    public const FEE_NOW = 'now';           // fee is added to the order total; GozakMart pays the marketplace
    public const FEE_ON_DELIVERY = 'on_delivery'; // the customer pays the courier's rider in cash

    private const OPTION_TTL_MINUTES = 120;

    /** Marketplace webhook type → our shipment status. */
    private const EVENT_STATUS = [
        'delivery.created'         => 'booked',
        'delivery.assigned'        => 'assigned',
        'delivery.driver_arrived'  => 'assigned',
        'delivery.picked_up'       => 'picked_up',
        'delivery.at_pickup_point' => 'picked_up',
        'delivery.delivered'       => 'delivered',
        'delivery.confirmed'       => 'delivered',
        'delivery.failed'          => 'delivery_failed',
        'delivery.cancelled'       => 'cancelled',
        'delivery.returned'        => 'returned',
    ];

    /** Marketplace shipment status (polling) → our shipment status. */
    private const SHIPMENT_STATUS = [
        'awaiting_payment'  => 'booked',
        'awaiting_dispatch' => 'booked',
        'offered'           => 'booked',
        'assigned'          => 'assigned',
        'heading_to_pickup' => 'assigned',
        'in_transit'        => 'picked_up',
        'delivered'         => 'delivered',
        'failed_attempt'    => 'delivery_failed',
        'cancelled'         => 'cancelled',
        'returned'          => 'returned',
    ];

    public function settings(): LogisticsSetting
    {
        return LogisticsSetting::current();
    }

    private function client(): LogisticsClient
    {
        return new LogisticsClient($this->settings());
    }

    // ── Connection ───────────────────────────────────────────────────────────

    /** Ask the marketplace who we are; remember the marketplace name and whether GozakMart is verified. */
    public function testConnection(): array
    {
        $s = $this->settings();
        try {
            $me = $this->client()->me();
        } catch (LogisticsException $e) {
            $s->forceFill(['last_error' => $e->getMessage(), 'last_checked_at' => now()])->save();
            throw $e;
        }
        $s->forceFill([
            'merchant_code'     => $me['merchant_code'] ?? null,
            'merchant_name'     => $me['merchant_name'] ?? null,
            'merchant_status'   => $me['status'] ?? null,
            'environment'       => $me['environment'] ?? null,
            'marketplace_name'  => $me['marketplace']['name'] ?? null,
            'marketplace_badge' => $me['marketplace']['badge_text'] ?? null,
            'marketplace_logo'  => $me['marketplace']['badge_logo'] ?? null,
            'own_provider_name' => $me['own_provider']['name'] ?? null,
            'last_checked_at'   => now(),
            'last_error'        => null,
        ])->save();
        return $me;
    }

    // ── Checkout ─────────────────────────────────────────────────────────────

    /**
     * Delivery choices for a cart going to an address.
     * $address: street, city, lga, state, landmark, name, phone_number, latitude, longitude
     */
    public function checkoutOptions(?int $userId, array $address, array $items = []): array
    {
        $s = $this->settings();
        $options = [];
        $notice = null;
        $feeLabels = [self::FEE_NOW => 'Pay delivery now with your order'];
        if ($s->allow_receiver_pays) {
            $feeLabels[self::FEE_ON_DELIVERY] = 'Pay the rider in cash on delivery';
        }

        if ($s->own_delivery_enabled || !$s->isLive()) {
            $shipping = $this->ownShipping($items);
            $options[] = [
                'id' => self::OWN, 'type' => 'store', 'name' => $s->own_delivery_label ?: 'GozakMart Delivery',
                'provider' => 'GozakMart', 'provider_logo' => null, 'rating' => null, 'rating_count' => 0,
                'service' => 'Delivered by GozakMart riders', 'eta' => $s->own_delivery_eta ?: 'Same day',
                'price' => $shipping, 'tags' => $shipping <= 0 ? ['free'] : [], 'fee_payers' => [self::FEE_NOW],
            ] + ($s->ownViaMarketplace() ? [
                // Run by GozakMart's team on the marketplace: the rider needs the map pin, and the customer can follow them live.
                'marketplace' => $s->marketplace_name, 'needs_pin' => true, 'live_tracking' => true,
            ] : []);
        }

        $marketplace = null;
        if ($s->isLive()) {
            // Known from the connection, so the app can name the marketplace before any courier is quoted.
            if ($s->marketplace_name) {
                $marketplace = ['name' => $s->marketplace_name, 'badge' => $s->marketplace_badge, 'logo' => $s->marketplace_logo];
            }
            $lat = $address['latitude'] ?? null;
            $lng = $address['longitude'] ?? null;
            $pickup = $this->pickupPoint(null);
            if (!is_numeric($lat) || !is_numeric($lng)) {
                $notice = match (true) {
                    $s->ownViaMarketplace() => 'Pin your delivery address on the map so our rider can find you' . ($marketplace ? ", and to see couriers from {$marketplace['name']}." : '.'),
                    (bool) $marketplace     => "Pin your delivery address on the map to see couriers from {$marketplace['name']} and their prices.",
                    default                 => 'Pin your delivery address on the map to see courier options and prices.',
                };
            } elseif (!$pickup) {
                $notice = null; // a set-up problem for staff, not the customer
                Log::warning('Logistics: pickup branch has no map position; couriers hidden at checkout.');
            } else {
                try {
                    $res = $this->client()->options([
                        'pickup'   => $pickup,
                        'dropoff'  => $this->dropoffFrom($address),
                        'weight_g' => (int) $s->default_weight_g,
                    ]);
                    $marketplace = [
                        'name'  => $res['marketplace']['name'] ?? $s->marketplace_name,
                        'badge' => $res['marketplace']['badge_text'] ?? $s->marketplace_badge,
                        'logo'  => $res['marketplace']['badge_logo'] ?? $s->marketplace_logo,
                    ];
                    foreach ($res['options'] ?? [] as $o) {
                        $opt = [
                            'id'            => $o['option_id'],
                            'type'          => 'courier',
                            'name'          => $o['provider']['name'] ?? 'Delivery partner',
                            'provider'      => $o['provider']['name'] ?? 'Delivery partner',
                            'provider_logo' => $o['provider']['logo'] ?? null,
                            'rating'        => $o['provider']['rating'] ?? null,
                            'rating_count'  => (int) ($o['provider']['rating_count'] ?? 0),
                            'service'       => $o['service']['name'] ?? 'Delivery',
                            'eta'           => $o['eta']['label'] ?? null,
                            'price'         => round(((int) $o['price']) / 100, 2),
                            'tags'          => $o['tags'] ?? [],
                            'fee_payers'    => array_keys($feeLabels),
                            'marketplace'   => $marketplace['name'],
                        ];
                        $options[] = $opt;
                        Cache::put($this->cacheKey($userId, $opt['id']), $opt + ['provider_id' => $o['provider']['id'] ?? null], now()->addMinutes(self::OPTION_TTL_MINUTES));
                    }
                } catch (LogisticsException $e) {
                    Log::info('Logistics options unavailable: ' . $e->getMessage());
                    if (!$options) {
                        throw $e;
                    }
                }
            }
        }

        return [
            'marketplace'      => $marketplace,
            'options'          => $options,
            'fee_payer_labels' => $feeLabels,
            'notice'           => $notice,
            'default_option'   => $options[0]['id'] ?? null,
        ];
    }

    /** What the customer pays GozakMart for delivery now, and the option behind it. */
    public function chosen(?int $userId, ?string $optionId, ?string $feePayer, float $ownShipping): array
    {
        if (!$optionId || $optionId === self::OWN) {
            $s = $this->settings();
            if (!$s->ownViaMarketplace()) {
                return ['shipping' => $ownShipping, 'option' => null, 'fee_payer' => self::FEE_NOW];
            }
            // GozakMart Delivery, run by GozakMart's own team on the marketplace.
            return ['shipping' => $ownShipping, 'fee_payer' => self::FEE_NOW, 'option' => [
                'id' => 'own', 'mode' => 'own', 'provider' => $s->own_delivery_label ?: 'GozakMart Delivery', 'provider_logo' => null,
                'rating' => null, 'service' => 'Delivered by GozakMart riders', 'eta' => $s->own_delivery_eta ?: 'Same day',
                'price' => $ownShipping, 'marketplace' => $s->marketplace_name,
            ]];
        }
        $opt = Cache::get($this->cacheKey($userId, $optionId));
        if (!$opt) {
            throw new LogisticsException('Your delivery choice has expired. Please choose a delivery option again.');
        }
        $feePayer = $feePayer === self::FEE_ON_DELIVERY && $this->settings()->allow_receiver_pays ? self::FEE_ON_DELIVERY : self::FEE_NOW;
        return [
            'shipping'  => $feePayer === self::FEE_NOW ? (float) $opt['price'] : 0.0,
            'option'    => $opt,
            'fee_payer' => $feePayer,
        ];
    }

    /** Save the customer's courier on a new order (inside the order transaction). */
    public function attach(Order $order, array $chosen): ?OrderShipment
    {
        if (!$chosen['option']) {
            return null;
        }
        $o = $chosen['option'];
        $shipment = OrderShipment::create([
            'order_id'         => $order->id,
            'carrier'          => 'logistics',
            'mode'             => ($o['mode'] ?? null) === 'own' ? 'own' : 'courier',
            'option_id'        => $o['id'],
            'service_name'     => $o['service'] ?? null,
            'provider_id'      => $o['provider_id'] ?? null,
            'provider_name'    => $o['provider'] ?? null,
            'provider_logo'    => $o['provider_logo'] ?? null,
            'provider_rating'  => $o['rating'] ?? null,
            'marketplace_name' => $o['marketplace'] ?? $this->settings()->marketplace_name,
            'eta_label'        => $o['eta'] ?? null,
            'quoted_price'     => $o['price'],
            'fee_payer'        => $chosen['fee_payer'] === self::FEE_ON_DELIVERY ? 'receiver' : 'merchant',
            'status'           => 'selected',
        ]);
        // Keeps the order off GozakMart's own dispatch board.
        $order->forceFill(['dispatch_status' => 'courier'])->saveQuietly();
        return $shipment;
    }

    // ── Booking ──────────────────────────────────────────────────────────────

    /** Book the chosen courier now that the parcels are packed and checked. Safe to retry. */
    public function book(Order $order): OrderShipment
    {
        $order->loadMissing(['shipment', 'parcels', 'items', 'shippingAddress', 'billingAddress', 'user', 'customer', 'branch']);
        $shipment = $order->shipment;
        if (!$shipment) {
            throw new LogisticsException('This order is not going with a marketplace courier.');
        }
        if (!$shipment->canBook()) {
            throw new LogisticsException("The courier is already {$shipment->label()}.");
        }
        $s = $this->settings();
        if (!$s->isLive()) {
            throw new LogisticsException('The logistics marketplace is switched off or GozakMart is not verified on it.');
        }
        $addr = DeliveryInfo::address($order);
        if (!$addr || !is_numeric($addr->latitude) || !is_numeric($addr->longitude)) {
            throw new LogisticsException('The delivery address has no map pin. Ask the customer to update it, or switch to GozakMart riders.');
        }
        $pickup = $this->pickupPoint($order->stock_location_id);
        if (!$pickup) {
            throw new LogisticsException('The packing branch has no map position. Set latitude/longitude on the stock location.');
        }

        $parcels = $order->parcels;
        $fragile = $parcels->contains(fn ($p) => array_intersect($p->handling ?? [], ['fragile', 'eggs', 'liquid']));
        $weight  = (int) round($parcels->sum(fn ($p) => (float) $p->weight_kg) * 1000) ?: (int) $s->default_weight_g;
        $unpaid  = $order->payment_status !== 'paid';

        $who = $shipment->isOwn()
            // GozakMart's own team: the price is what the customer paid for GozakMart Delivery (none: the team's price list).
            ? array_filter(['provider' => 'own', 'price' => (int) round(((float) $order->shipping_cost) * 100) ?: null])
            : [
                'option_id' => $shipment->option_id,
                'max_price' => (int) ceil(((float) $shipment->quoted_price) * 100 * (1 + ((int) $s->max_price_increase_percent) / 100)),
                'fee_payer' => $shipment->fee_payer,
            ];
        $body = ['external_order_id' => (string) $order->id] + $who + [
            'pickup'            => $pickup,
            'dropoff'           => $this->dropoffFrom([
                'street' => $addr->street, 'city' => $addr->city, 'lga' => $addr->lga ?? null, 'state' => $addr->state,
                'landmark' => $addr->landmark ?? null, 'latitude' => $addr->latitude, 'longitude' => $addr->longitude,
                'name' => DeliveryInfo::recipient($order), 'phone_number' => DeliveryInfo::phone($order),
            ]),
            'weight_g'          => $weight,
            'fragile'           => (bool) $fragile,
            'declared_value'    => (int) round(((float) $order->total_amount - (float) $order->shipping_cost) * 100),
            // Goods money the courier collects for GozakMart on an unpaid (pay-on-delivery) order.
            'cod_amount'        => $unpaid ? (int) round((float) $order->total_amount * 100) : 0,
            'packages'          => $parcels->map(fn ($p) => [
                'description' => trim($p->typeLabel() . ' ' . $p->code . ($p->contents ? ' — ' . $p->contents : '')),
                'weight_g'    => $p->weight_kg ? (int) round((float) $p->weight_kg * 1000) : null,
                'fragile'     => (bool) array_intersect($p->handling ?? [], ['fragile', 'eggs', 'liquid']),
            ])->values()->all(),
        ];

        $shipment->forceFill(['status' => 'booking', 'attempts' => $shipment->attempts + 1])->save();
        try {
            $res = $this->client()->createDelivery($body);
        } catch (LogisticsException $e) {
            $shipment->forceFill(['status' => 'failed', 'last_error' => mb_substr($e->getMessage(), 0, 500)])->save();
            DispatchEvent::log('courier_failed', $order, null, ['note' => $e->getMessage()]);
            throw $e;
        }

        $shipment->forceFill([
            'status'                => 'booked',
            'agreement'             => $res['agreement'] ?? null,
            'external_order_number' => $res['order'] ?? null,
            'tracking_url'          => $res['tracking_url'] ?? null,
            'payment_url'           => $res['payment_url'] ?? null,
            'price'                 => isset($res['price']) ? round(((int) $res['price']) / 100, 2) : $shipment->quoted_price,
            // The customer keeps seeing "GozakMart Delivery" for the own team.
            'provider_name'         => $shipment->isOwn() ? $shipment->provider_name : ($res['provider']['name'] ?? $shipment->provider_name),
            'provider_id'           => $res['provider']['id'] ?? $shipment->provider_id,
            'booked_at'             => now(),
            'last_error'            => !empty($res['payment_url']) ? 'GozakMart pays per delivery on this marketplace: open the payment link to release the booking.' : null,
            'payload'               => $res,
        ])->save();
        $order->forceFill(['dispatch_status' => 'courier'])->saveQuietly();
        DispatchEvent::log('courier_booked', $order, null, ['note' => "{$shipment->provider_name} · " . ($shipment->marketplace_name ?? 'marketplace') . ' · ₦' . number_format((float) $shipment->price, 2)]);

        return $shipment;
    }

    /** Called after packing is verified. Returns a message for staff, or null when nothing to do. */
    public function bookIfReady(Order $order): ?string
    {
        $order->loadMissing('shipment');
        if (!$order->shipment || !$order->shipment->canBook()) {
            return null;
        }
        try {
            $s = $this->book($order);
            if ($s->isOwn()) {
                $team = $s->payload['provider']['name'] ?? $this->settings()->own_provider_name ?? 'our delivery team';
                return "Sent to {$team}" . ($s->marketplace_name ? " on {$s->marketplace_name}" : '') . '. Assign the rider there.';
            }
            return "Courier booked with {$s->provider_name}" . ($s->marketplace_name ? " via {$s->marketplace_name}" : '') . '.';
        } catch (LogisticsException $e) {
            return 'Courier not booked yet: ' . $e->getMessage();
        }
    }

    /** Staff give up on the courier: cancel it on the marketplace (if booked) and deliver with GozakMart riders. */
    public function switchToOwnRiders(Order $order, ?string $reason = null): void
    {
        $order->loadMissing('shipment');
        $sh = $order->shipment;
        if ($sh && in_array($sh->status, ['booked', 'assigned'], true)) {
            $this->client()->cancel((string) $order->id, 'merchant_cancelled');
        }
        if ($sh && in_array($sh->status, ['picked_up', 'delivered'], true)) {
            throw new LogisticsException('The courier already has the parcels.');
        }
        DB::transaction(function () use ($order, $sh, $reason) {
            if ($sh) {
                $sh->forceFill(['status' => 'cancelled', 'last_error' => $reason ?: 'Switched to GozakMart riders'])->save();
            }
            $order->forceFill(['dispatch_status' => null])->saveQuietly();
        });
        DispatchEvent::log('courier_cancelled', $order, null, ['note' => $reason ?: 'Switched to GozakMart riders']);
    }

    // ── Live tracking for the customer app ───────────────────────────────────

    public const LIVE_CACHE_SECONDS = 5;

    /**
     * The rider on the map for one order: status, rider (first name, vehicle, position, ETA), the shop and the
     * customer's pin, the delivery code to give the rider, and the "I'm ready / call me" replies. Null when the order
     * has no booked marketplace delivery.
     */
    public function liveTracking(Order $order): ?array
    {
        $order->loadMissing('shipment');
        $sh = $order->shipment;
        if (!$sh || !in_array($sh->status, ['booked', 'assigned', 'picked_up', 'delivery_failed', 'delivered'], true)) {
            return null;
        }
        $t = Cache::remember('logistics_live:' . $order->id, self::LIVE_CACHE_SECONDS, fn () => $this->client()->tracking((string) $order->id));

        // Keep the order in step while the customer is watching (webhooks remain the main path).
        $status = self::SHIPMENT_STATUS[$t['delivery_status'] ?? ''] ?? null;
        if ($status && $status !== $sh->status) {
            $this->apply($sh, $status, $t, 'live');
            $sh->refresh();
        }

        $d = $t['driver'] ?? null;
        $stops = collect($t['stops'] ?? []);
        $place = fn ($type) => ($x = $stops->where('type', $type)->last()) ? ['lat' => (float) $x['lat'], 'lng' => (float) $x['lng'], 'address' => $x['address'] ?? null] : null;

        return [
            'status'        => $sh->status,
            'status_label'  => $sh->label(),
            'provider'      => $sh->provider_name,
            'marketplace'   => $sh->marketplace_name,
            'mode'          => $sh->mode ?: 'courier',
            'tracking_code' => $t['tracking_code'] ?? $sh->tracking_code,
            'pickup'        => $place('pickup'),
            'dropoff'       => $place('dropoff'),
            'rider'         => $d ? [
                'first_name'           => $d['first_name'] ?? 'Your rider',
                'vehicle'              => $d['vehicle'] ?? null,
                'rating'               => isset($d['rating']) ? round((float) $d['rating'], 1) : null,
                'lat'                  => isset($d['lat']) ? (float) $d['lat'] : null,
                'lng'                  => isset($d['lng']) ? (float) $d['lng'] : null,
                'seconds_since_update' => isset($d['seconds_since_update']) ? (int) abs((float) $d['seconds_since_update']) : null,
                'eta'                  => $d['eta']['label'] ?? null,
                'eta_to'               => $d['eta']['to'] ?? null,        // pickup | dropoff
                'eta_stale'            => (bool) ($d['eta']['stale'] ?? false),
            ] : null,
            'delivery_code' => $t['delivery_code'] ?? null,
            'reply'         => $t['reply'] ?? null,
            'can_reply'     => (bool) ($t['can_reply'] ?? false),
            'reply_options' => $t['reply_options'] ?? [],
            'refresh_after' => max(8, (int) ($t['poll_after_seconds'] ?? 15)),
            'delivered_at'  => $t['delivered_at'] ?? null,
        ];
    }

    /** The customer answers the rider from the app ("I'm ready", "Call me when you arrive"…). */
    public function replyToRider(Order $order, string $reply, ?string $note = null): string
    {
        $order->loadMissing('shipment');
        if (!$order->shipment || !$order->shipment->isLive()) {
            throw new LogisticsException('This delivery is not on its way yet.');
        }
        $res = $this->client()->reply((string) $order->id, $reply, $note);
        Cache::forget('logistics_live:' . $order->id);
        return $res['message'] ?? 'The rider has been told.';
    }

    // ── Updates from the marketplace ─────────────────────────────────────────

    /**
     * POST /api/webhooks/logistics. Header X-Logistics-Signature: t=<unix>,v1=<hmac_sha256(secret, "t.body")>.
     * @return array{0:int,1:string} http status and message
     */
    public function handleWebhook(string $raw, ?string $signature): array
    {
        $secret = $this->settings()->webhook_secret;
        if (!$secret) {
            return [503, 'Webhook secret not configured'];
        }
        $parts = [];
        foreach (explode(',', (string) $signature) as $kv) {
            [$k, $v] = array_pad(explode('=', trim($kv), 2), 2, null);
            $parts[$k] = $v;
        }
        $t = (int) ($parts['t'] ?? 0);
        if (!$t || abs(time() - $t) > 300 || !hash_equals(hash_hmac('sha256', "{$t}.{$raw}", $secret), (string) ($parts['v1'] ?? ''))) {
            return [401, 'Bad signature'];
        }
        $evt = json_decode($raw, true);
        if (!is_array($evt) || empty($evt['id']) || empty($evt['type'])) {
            return [400, 'Bad payload'];
        }
        $data = $evt['data'] ?? [];
        $orderId = (string) ($data['external_order_id'] ?? '');

        $fresh = DB::table('logistics_webhook_events')->insertOrIgnore([
            'event_id' => (string) $evt['id'], 'type' => (string) $evt['type'], 'order_id' => $orderId ?: null,
            'payload' => $raw, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (!$fresh) {
            return [200, 'Already processed'];
        }
        $shipment = $orderId ? OrderShipment::where('order_id', $orderId)->first() : null;
        if ($shipment && isset(self::EVENT_STATUS[$evt['type']])) {
            $this->apply($shipment, self::EVENT_STATUS[$evt['type']], $data, 'webhook');
        }
        DB::table('logistics_webhook_events')->where('event_id', $evt['id'])->update(['processed_at' => now()]);
        return [200, $shipment ? 'OK' : 'Ignored (unknown order)'];
    }

    /** Poll one courier delivery (backup for missed webhooks). */
    public function sync(OrderShipment $shipment): void
    {
        $d = $this->client()->delivery((string) $shipment->order_id);
        $status = self::SHIPMENT_STATUS[$d['delivery_status'] ?? ''] ?? null;
        $shipment->forceFill(['synced_at' => now(), 'tracking_code' => $d['tracking_code'] ?? $shipment->tracking_code,
            'tracking_url' => $d['tracking_url'] ?? $shipment->tracking_url, 'eta_label' => $d['eta']['label'] ?? $shipment->eta_label])->save();
        if ($status) {
            $this->apply($shipment, $status, $d, 'sync');
        }
    }

    /** Move the shipment (forward only) and carry the milestone onto the order. */
    public function apply(OrderShipment $shipment, string $status, array $data, string $source): void
    {
        $rank = ['selected' => 0, 'booking' => 1, 'failed' => 1, 'booked' => 2, 'assigned' => 3, 'delivery_failed' => 4, 'picked_up' => 4, 'delivered' => 9, 'cancelled' => 9, 'returned' => 9];
        $final = in_array($shipment->status, ['delivered', 'cancelled', 'returned'], true);
        if ($final || (($rank[$status] ?? 0) < ($rank[$shipment->status] ?? 0) && $status !== 'delivery_failed')) {
            return;
        }
        if ($shipment->status === 'booking') {
            // The marketplace's "created" can arrive while book() is still waiting for its answer; book() records it.
            return;
        }
        $order = $shipment->order;
        $fill = ['status' => $status, 'external_status' => $data['status'] ?? ($data['delivery_status'] ?? $status),
            'tracking_code' => $data['tracking_code'] ?? $shipment->tracking_code];
        if ($status === 'picked_up' && !$shipment->picked_up_at) $fill['picked_up_at'] = now();
        if ($status === 'delivered') $fill['delivered_at'] = now();
        if ($status === 'delivery_failed') $fill['last_error'] = 'Delivery attempt failed' . (!empty($data['reason']) ? ': ' . $data['reason'] : '');
        $changed = $shipment->status !== $status;
        $shipment->forceFill($fill)->save();
        if (!$changed || !$order) {
            return;
        }

        if ($status === 'picked_up' && in_array($order->status, ['pending', 'processing'], true)) {
            $order->status = 'shipped';
            $order->save();
            $this->notify($order, 'shipped');
        } elseif ($status === 'delivered' && $order->status !== 'delivered') {
            $order->status = 'delivered';
            $order->save();
            $this->notify($order, 'delivered');
        } elseif (in_array($status, ['cancelled', 'returned'], true)) {
            // Back to GozakMart: it shows on the dispatch board again.
            $order->forceFill(['dispatch_status' => 'returned', 'dispatch_note' => "Courier {$status} ({$shipment->provider_name})"])->saveQuietly();
            if ($order->status === 'shipped') {
                $order->status = 'processing';
                $order->save();
            }
        }
        DispatchEvent::log('courier_' . $status, $order, null, ['note' => "{$shipment->provider_name} · via {$source}"]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function ownShipping(array $items): float
    {
        $pricing = app(OrderPricingService::class);
        if ($items) {
            try {
                return (float) $pricing->price($items)['shipping'];
            } catch (\Throwable $e) {
            }
        }
        return (float) ($pricing->settings()['shipping_fee'] ?? 0);
    }

    /** The branch the courier collects from, as the partner API wants it. */
    private function pickupPoint(?int $branchId): ?array
    {
        $b = ($branchId ? StockLocation::find($branchId) : null)
            ?? ($this->settings()->pickup_stock_location_id ? StockLocation::find($this->settings()->pickup_stock_location_id) : null)
            ?? StockLocation::getDefault();
        if (!$b || !is_numeric($b->latitude) || !is_numeric($b->longitude)) {
            return null;
        }
        return array_filter([
            'line1'         => mb_substr(trim(($b->address ?: $b->name) . ''), 0, 255),
            'lat'           => (float) $b->latitude,
            'lng'           => (float) $b->longitude,
            'contact_name'  => mb_substr($b->contact_person ?: ('GozakMart ' . $b->name), 0, 120),
            'contact_phone' => $b->phone ? mb_substr($b->phone, 0, 24) : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function dropoffFrom(array $a): array
    {
        $line = collect([$a['street'] ?? null, $a['city'] ?? null, $a['lga'] ?? null, $a['state'] ?? null])
            ->filter()->unique(fn ($v) => mb_strtolower(trim($v)))->implode(', ');
        return array_filter([
            'line1'         => mb_substr($line ?: 'Customer address', 0, 255),
            'lat'           => (float) $a['latitude'],
            'lng'           => (float) $a['longitude'],
            'contact_name'  => isset($a['name']) ? mb_substr((string) $a['name'], 0, 120) : null,
            'contact_phone' => isset($a['phone_number']) ? mb_substr((string) $a['phone_number'], 0, 24) : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function cacheKey(?int $userId, string $optionId): string
    {
        return 'logistics_opt:' . ($userId ?: 'guest') . ':' . $optionId;
    }

    private function notify(Order $order, string $status): void
    {
        try {
            app(\App\Services\OrderNotificationService::class)->notifyOrderStatusUpdate($order->loadMissing('user'), $status);
        } catch (\Throwable $e) {
            Log::warning('Courier status push failed: ' . $e->getMessage());
        }
    }
}
