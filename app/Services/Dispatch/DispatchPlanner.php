<?php

namespace App\Services\Dispatch;

use App\Models\Dispatch\DeliveryRider;
use App\Models\Dispatch\DeliveryZone;
use App\Models\Dispatch\DispatchBatch;
use App\Models\Dispatch\DispatchEvent;
use App\Models\Dispatch\DispatchSetting;
use App\Models\Order;
use App\Support\DeliveryInfo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The "intelligent" part of dispatch:
 *   suggestions() – proposes runs: zone → promised time → route order, cut to a rider's capacity, best free rider
 *   alerts()      – what needs a human now (late, stuck, cash not handed in, suspicious codes)
 *   performance() – per-rider delivery, punctuality and cash numbers
 */
class DispatchPlanner
{
    private DispatchSetting $s;

    public function __construct()
    {
        $this->s = DispatchSetting::current();
    }

    // ── Suggested runs ───────────────────────────────────────────────────────

    public function suggestions(?int $branchId = null): Collection
    {
        $orders = Order::with(['parcels', 'shippingAddress', 'billingAddress', 'deliveryZone', 'user', 'customer'])
            ->whereNull('dispatch_batch_id')
            ->whereIn('status', ['pending', 'processing'])
            ->where('packing_status', 'verified')
            ->where(fn ($q) => $q->whereNull('dispatch_status')->orWhere('dispatch_status', '!=', 'courier'))
            ->when($branchId, fn ($q) => $q->where('stock_location_id', $branchId))
            ->get();
        if ($orders->isEmpty()) {
            return collect();
        }

        $busy = DispatchBatch::whereIn('status', ['draft', 'loading', 'dispatched'])->whereNotNull('rider_id')->pluck('rider_id')->all();
        $runsToday = DispatchBatch::where('dispatched_at', '>=', now()->startOfDay())->whereNotNull('rider_id')
            ->selectRaw('rider_id, COUNT(*) n')->groupBy('rider_id')->pluck('n', 'rider_id');
        $free = DeliveryRider::active()->whereNotIn('id', $busy)->get()
            ->each(fn ($r) => $r->setAttribute('runs_today', (int) ($runsToday[$r->id] ?? 0)));

        $groups = $orders->groupBy(fn ($o) => (int) $o->delivery_zone_id)
            ->map(fn ($g) => $this->routeOrder($g))
            ->sortBy(fn ($g) => $g->first()->promisedAt()?->timestamp ?? PHP_INT_MAX);

        $out = collect();
        foreach ($groups as $zoneId => $queue) {
            $zone = $queue->first()->deliveryZone;
            $branch = $queue->pluck('stock_location_id')->filter()->first();
            while ($queue->isNotEmpty()) {
                $parcelsLeft = $queue->sum(fn ($o) => max(1, $o->parcels->count()));
                $rider = $this->pickRider($free, $parcelsLeft, $branch);
                $cap   = $rider?->capacity ?: $this->s->default_capacity;

                $take = collect();
                $parcels = 0;
                foreach ($queue as $o) {
                    $n = max(1, $o->parcels->count());
                    if ($take->isNotEmpty() && ($parcels + $n > $cap || $take->count() >= $this->s->max_stops_per_run)) {
                        break;
                    }
                    $take->push($o);
                    $parcels += $n;
                }
                $queue = $queue->reject(fn ($o) => $take->contains('id', $o->id))->values();
                if ($rider) {
                    $free = $free->reject(fn ($r) => $r->id === $rider->id)->values();
                }
                $out->push($this->describe($take, $zone, $rider, $parcels, $cap));
            }
        }

        return $out->sortBy('sort')->values();
    }

    /** Within a zone: earliest promise first, then the zone's area (driving) order. */
    private function routeOrder(Collection $orders): Collection
    {
        $zone  = $orders->first()->deliveryZone;
        $areas = array_map(fn ($a) => mb_strtolower(trim($a)), $zone?->areas ?? []);
        $rank = function (Order $o) use ($areas) {
            $a = DeliveryInfo::address($o);
            $text = mb_strtolower(implode(' ', array_filter([$a->lga ?? null, $a->city ?? null, $a->street ?? null, $a->landmark ?? null])));
            foreach ($areas as $i => $kw) {
                if ($kw !== '' && str_contains($text, $kw)) {
                    return $i;
                }
            }
            return 999;
        };
        // Bucket promises by hour so a 10:05 order doesn't jump ahead of a 10:00 one across town.
        return $orders->sortBy(fn ($o) => sprintf('%012d-%03d', $o->promisedAt() ? (int) floor($o->promisedAt()->timestamp / 3600) : 999999999999, $rank($o)))->values();
    }

    /** Smallest free vehicle that fits everything (keeps vans for big loads); else the biggest. Home branch first, then fewest runs today. */
    private function pickRider(Collection $free, int $parcels, $branchId): ?DeliveryRider
    {
        if ($free->isEmpty()) {
            return null;
        }
        $cap = fn ($r) => $r->capacity ?: $this->s->default_capacity;
        $candidates = $free->sortBy(fn ($r) => [
            ($branchId && $r->stock_location_id && (int) $r->stock_location_id !== (int) $branchId) ? 1 : 0,
            $cap($r) >= $parcels ? 0 : 1,
            $cap($r) >= $parcels ? $cap($r) : -$cap($r),
            $r->runs_today,
        ]);
        return $candidates->first();
    }

    private function describe(Collection $orders, ?DeliveryZone $zone, ?DeliveryRider $rider, int $parcels, int $cap): array
    {
        $earliest = $orders->map(fn ($o) => $o->promisedAt())->filter()->sort()->first();
        $mins     = $earliest ? now()->diffInMinutes($earliest, false) : null;
        $level    = $mins === null ? 'normal' : ($mins < 0 ? 'overdue' : ($mins <= $this->s->at_risk_minutes ? 'urgent' : 'normal'));
        $cod      = $orders->sum(fn ($o) => DeliveryInfo::amountToCollect($o));

        $why = [];
        $why[] = $orders->count() . ' stop' . ($orders->count() > 1 ? 's' : '') . ' in ' . ($zone?->name ?? 'unzoned areas');
        if ($earliest) {
            $why[] = ($mins < 0 ? 'overdue since ' : 'earliest due ') . $earliest->format('D g:i A');
        }
        if ($rider) {
            $why[] = "{$parcels}/{$cap} parcels on {$rider->name}'s " . strtolower(DeliveryRider::VEHICLES[$rider->vehicle_type] ?? 'vehicle');
            if ($rider->runs_today) {
                $why[] = "{$rider->runs_today} run(s) done today";
            }
        } else {
            $why[] = 'no free rider right now';
        }

        return [
            'zone'     => $zone,
            'orders'   => $orders,
            'rider'    => $rider,
            'parcels'  => $parcels,
            'capacity' => $cap,
            'cod'      => $cod,
            'earliest' => $earliest,
            'level'    => $level,
            'why'      => implode(' · ', $why),
            'sort'     => ($level === 'overdue' ? 0 : ($level === 'urgent' ? 1 : 2)) * 1e12 + ($earliest?->timestamp ?? 9e11),
        ];
    }

    // ── Alerts ───────────────────────────────────────────────────────────────

    public function alerts(?int $branchId = null): Collection
    {
        $s = $this->s;
        $alerts = collect();
        $branch = fn ($q) => $branchId ? $q->where('stock_location_id', $branchId) : $q;
        $refs = fn (Collection $c) => $c->take(6)->map(fn ($o) => $o->ref)->implode(', ') . ($c->count() > 6 ? '…' : '');

        // 1–2. Promised time passed / close, not delivered
        $active = $branch(Order::query())
            ->whereIn('status', ['pending', 'processing', 'shipped'])
            ->whereNotNull('delivery_date')
            ->where('delivery_date', '<=', now()->addMinutes($s->at_risk_minutes)->endOfDay())
            ->get(['id', 'invoice_number', 'delivery_date', 'status', 'dispatch_status', 'dispatch_batch_id', 'packing_status']);
        $overdue = $active->filter(fn ($o) => $o->promisedAt()?->isPast());
        $atRisk  = $active->filter(fn ($o) => !$o->promisedAt()?->isPast()
            && $o->promisedAt()?->lte(now()->addMinutes($s->at_risk_minutes))
            && $o->dispatch_status !== 'out_for_delivery');
        if ($overdue->count()) {
            $alerts->push($this->alert('danger', 'ri-alarm-warning-line', "{$overdue->count()} order(s) past their promised delivery time", $refs($overdue), route('dispatch.index')));
        }
        if ($atRisk->count()) {
            $alerts->push($this->alert('warning', 'ri-timer-flash-line', "{$atRisk->count()} order(s) due within " . $this->human($s->at_risk_minutes) . ' and not on the road', $refs($atRisk), route('dispatch.index')));
        }

        // 3. Paid / pay-on-delivery orders not packed in time
        $notPacked = $branch(Order::query())
            ->whereIn('status', ['pending', 'processing'])
            ->whereIn('packing_status', ['unpacked', 'packing'])
            ->where('created_at', '<=', now()->subMinutes($s->pack_within_minutes))
            ->where(fn ($q) => $q->where('payment_status', 'paid')
                ->orWhere('payment_method', 'like', '%deliver%')
                ->orWhere('payment_method', 'like', '%cash%')
                ->orWhere('payment_method', 'like', '%cod%'))
            ->orderBy('created_at')->get(['id', 'invoice_number', 'created_at']);
        if ($notPacked->count()) {
            $alerts->push($this->alert('warning', 'ri-shopping-basket-2-line', "{$notPacked->count()} order(s) waiting more than " . $this->human($s->pack_within_minutes) . ' to be packed', $refs($notPacked), route('adminorders.index')));
        }

        // 4. Packed but nobody checked it
        $unchecked = $branch(Order::query())->whereNull('dispatch_batch_id')->where('packing_status', 'packed')
            ->where('packed_at', '<=', now()->subMinutes(30))->get(['id', 'invoice_number']);
        if ($unchecked->count()) {
            $alerts->push($this->alert('info', 'ri-shield-check-line', "{$unchecked->count()} packed order(s) waiting for a second check", $refs($unchecked), route('dispatch.index')));
        }

        // 5. Batches stuck before leaving
        $stuck = $branch(DispatchBatch::query())->whereIn('status', ['draft', 'loading'])
            ->where(fn ($q) => $q->where('created_at', '<=', now()->subMinutes($s->loading_stuck_minutes))
                ->orWhere('planned_departure_at', '<=', now()->subMinutes(15)))
            ->get();
        foreach ($stuck as $b) {
            $alerts->push($this->alert('warning', 'ri-truck-line', "Batch {$b->code} ({$b->route_code}) hasn't left yet",
                ($b->planned_departure_at ? 'Planned ' . $b->planned_departure_at->format('g:i A') . ' · ' : '') . 'created ' . $b->created_at->diffForHumans(), route('dispatch.show', $b)));
        }

        // 6. Runs out too long
        $long = $branch(DispatchBatch::query())->with('rider')->where('status', 'dispatched')
            ->where('dispatched_at', '<=', now()->subMinutes($s->run_overdue_minutes))->get();
        foreach ($long as $b) {
            $open = $b->orders()->where('dispatch_status', 'out_for_delivery')->count();
            $alerts->push($this->alert('danger', 'ri-time-line', "Run {$b->route_code} has been out " . $this->human((int) $b->dispatched_at->diffInMinutes(now())),
                ($b->rider?->name ?? 'No rider') . ($b->rider?->phone ? " · {$b->rider->phone}" : '') . " · {$open} stop(s) still open", route('dispatch.show', $b)));
        }

        // 7. Returned orders waiting for another run
        $returned = $branch(Order::query())->whereNull('dispatch_batch_id')->where('dispatch_status', 'returned')
            ->whereIn('status', ['pending', 'processing'])->get(['id', 'invoice_number']);
        if ($returned->count()) {
            $alerts->push($this->alert('info', 'ri-arrow-go-back-line', "{$returned->count()} returned order(s) need re-delivery", $refs($returned), route('dispatch.index')));
        }

        // 8. Cash not handed in / short
        $closed = $branch(DispatchBatch::query())->with('rider')->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(14))->get();
        foreach ($closed as $b) {
            $inHand = $b->cashCollectedInHand();
            if ($inHand <= 0) {
                continue;
            }
            if ($b->cash_received === null) {
                $alerts->push($this->alert('danger', 'ri-hand-coin-line', "₦" . number_format($inHand) . " cash from run {$b->route_code} not handed in",
                    ($b->rider?->name ?? '') . ' · closed ' . $b->completed_at?->diffForHumans(), route('dispatch.show', $b)));
            } elseif ((float) $b->cash_received + 0.01 < $inHand) {
                $alerts->push($this->alert('danger', 'ri-scales-3-line', "Cash short on run {$b->route_code}: ₦" . number_format($inHand - (float) $b->cash_received, 2),
                    ($b->rider?->name ?? '') . ' reported ₦' . number_format($inHand, 2) . ', handed in ₦' . number_format((float) $b->cash_received, 2), route('dispatch.show', $b)));
            }
        }

        // 9. Repeated wrong delivery codes (possible wrong address / attempted fraud)
        $codeFails = DispatchEvent::where('event', 'code_failed')->where('created_at', '>=', now()->subDay())
            ->selectRaw('order_id, rider_id, COUNT(*) n')->groupBy('order_id', 'rider_id')->havingRaw('COUNT(*) >= 3')->get();
        foreach ($codeFails as $f) {
            $o = Order::find($f->order_id);
            $r = DeliveryRider::find($f->rider_id);
            if ($o) {
                $alerts->push($this->alert('danger', 'ri-lock-password-line', "{$f->n} wrong delivery codes on order {$o->ref}",
                    ($r?->name ?? 'Rider') . ' — call the customer before the parcels are handed over', route('adminorders.show', $o->id)));
            }
        }

        $order = ['danger' => 0, 'warning' => 1, 'info' => 2];
        return $alerts->sortBy(fn ($a) => $order[$a['level']] ?? 3)->values();
    }

    private function alert(string $level, string $icon, string $title, string $detail, ?string $url): array
    {
        return compact('level', 'icon', 'title', 'detail', 'url');
    }

    private function human(int $minutes): string
    {
        if ($minutes < 90) {
            return "{$minutes} min";
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h . 'h' . ($m ? " {$m}m" : '');
    }

    // ── Performance ──────────────────────────────────────────────────────────

    public function performance(Carbon $from, Carbon $to, ?int $branchId = null): array
    {
        $events = DispatchEvent::whereIn('event', ['delivered', 'failed', 'code_failed', 'called'])
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('rider_id')
            ->get();
        $orderIds = $events->whereIn('event', ['delivered'])->pluck('order_id')->filter()->unique();
        $orders = Order::whereIn('id', $orderIds)
            ->when($branchId, fn ($q) => $q->where('stock_location_id', $branchId))
            ->get()->keyBy('id');

        $batches = DispatchBatch::whereBetween('dispatched_at', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('stock_location_id', $branchId))
            ->get();

        $riders = DeliveryRider::whereIn('id', $events->pluck('rider_id')->merge($batches->pluck('rider_id'))->filter()->unique())->get()->keyBy('id');

        $rows = [];
        foreach ($riders as $rid => $rider) {
            $ev = $events->where('rider_id', $rid);
            $delivered = $ev->where('event', 'delivered')->filter(fn ($e) => !$branchId || $orders->has($e->order_id));
            $failed = $ev->where('event', 'failed');
            $onTime = 0; $timed = 0; $mins = [];
            $methods = ['code' => 0, 'photo' => 0, 'admin' => 0, 'none' => 0];
            foreach ($delivered as $e) {
                $o = $orders->get($e->order_id);
                if (!$o) continue;
                if ($p = $o->promisedAt()) {
                    $timed++;
                    if ($e->created_at->lte($p)) $onTime++;
                }
                if ($o->out_for_delivery_at) {
                    $mins[] = $o->out_for_delivery_at->diffInMinutes($e->created_at);
                }
                $methods[$o->delivery_method ?: 'admin'] = ($methods[$o->delivery_method ?: 'admin'] ?? 0) + 1;
            }
            $rb = $batches->where('rider_id', $rid);
            $reported = 0.0; $handed = 0.0; $reconciledReported = 0.0;
            foreach ($rb as $b) {
                $inHand = $b->cashCollectedInHand();
                $reported += $inHand;
                if ($b->cash_received !== null) {
                    $handed += (float) $b->cash_received;
                    $reconciledReported += $inHand;
                }
            }
            $attempts = $delivered->count() + $failed->count();
            $rows[] = [
                'rider'      => $rider,
                'runs'       => $rb->count(),
                'delivered'  => $delivered->count(),
                'failed'     => $failed->count(),
                'success'    => $attempts ? round($delivered->count() / $attempts * 100) : null,
                'on_time'    => $timed ? round($onTime / $timed * 100) : null,
                'avg_mins'   => $mins ? (int) round(array_sum($mins) / count($mins)) : null,
                'methods'    => $methods,
                'code_fails' => $ev->where('event', 'code_failed')->count(),
                'calls'      => $ev->where('event', 'called')->count(),
                'cash_expected' => (float) $rb->sum('cash_expected'),
                'cash_reported' => $reported,
                'cash_handed'   => $handed,
                'cash_variance' => round($handed - $reconciledReported, 2),
                'cash_pending'  => round($reported - $reconciledReported, 2),
            ];
        }
        usort($rows, fn ($a, $b) => $b['delivered'] <=> $a['delivered']);

        $reasons = $events->where('event', 'failed')
            ->map(fn ($e) => trim(explode(' — ', (string) $e->note)[0]) ?: 'Unspecified')
            ->countBy()->sortDesc();

        $totDelivered = array_sum(array_column($rows, 'delivered'));
        $totFailed = array_sum(array_column($rows, 'failed'));

        return [
            'rows'    => $rows,
            'reasons' => $reasons,
            'totals'  => [
                'runs'      => $batches->count(),
                'delivered' => $totDelivered,
                'failed'    => $totFailed,
                'success'   => ($totDelivered + $totFailed) ? round($totDelivered / ($totDelivered + $totFailed) * 100) : null,
                'cash_expected' => array_sum(array_column($rows, 'cash_expected')),
                'cash_handed'   => array_sum(array_column($rows, 'cash_handed')),
                'cash_variance' => array_sum(array_column($rows, 'cash_variance')),
                'cash_pending'  => array_sum(array_column($rows, 'cash_pending')),
            ],
        ];
    }
}
