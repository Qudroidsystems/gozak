<?php

namespace App\Http\Controllers\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DeliveryRider;
use App\Models\Dispatch\DeliveryZone;
use App\Models\Dispatch\DispatchBatch;
use App\Models\Dispatch\OrderParcel;
use App\Models\Order;
use App\Models\StockLocation;
use App\Models\Dispatch\DispatchSetting;
use App\Services\Dispatch\DispatchPlanner;
use App\Services\Dispatch\DispatchService;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use RuntimeException;

class DispatchController extends Controller
{
    public function __construct(private DispatchService $dispatch)
    {
        $this->middleware('permission:View dispatch|Manage dispatch');
        $this->middleware('permission:Manage dispatch', ['except' => ['index', 'show', 'manifest', 'labels', 'lookup', 'performance', 'settings', 'saveSettings']]);
        $this->middleware('permission:Manage dispatch settings', ['only' => ['settings', 'saveSettings']]);
    }

    // ── Board ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $branchId = $request->integer('branch') ?: null;
        $tab      = $request->get('tab', 'board');

        $branches = StockLocation::active()->orderBy('name')->get(['id', 'name', 'code']);
        $zones    = DeliveryZone::active()->orderBy('sort_order')->orderBy('name')->get()->keyBy('id');
        $riders   = DeliveryRider::active()->orderBy('name')->get();

        $queue = $this->dispatch->readyQueue($branchId);

        $active = DispatchBatch::with(['rider', 'zone'])
            ->withCount(['orders', 'parcels', 'parcels as loaded_count' => fn ($q) => $q->whereIn('status', ['loaded', 'out_for_delivery', 'delivered'])])
            ->withCount(['orders as delivered_count' => fn ($q) => $q->where('dispatch_status', 'delivered')])
            ->whereIn('status', ['draft', 'loading', 'dispatched'])
            ->when($branchId, fn ($q) => $q->where('stock_location_id', $branchId))
            ->orderByRaw("CASE status WHEN 'loading' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END")
            ->latest()
            ->get();

        $history = $tab === 'history'
            ? DispatchBatch::with(['rider', 'zone'])->withCount(['orders', 'parcels'])
                ->whereIn('status', ['completed', 'cancelled'])
                ->when($branchId, fn ($q) => $q->where('stock_location_id', $branchId))
                ->latest()->paginate(25)->withQueryString()
            : null;

        $awaitingPacking = Order::whereNull('dispatch_batch_id')
            ->whereIn('status', ['pending', 'processing'])
            ->whereIn('packing_status', ['unpacked', 'packing'])
            ->when($branchId, fn ($q) => $q->where(fn ($w) => $w->where('stock_location_id', $branchId)->orWhereNull('stock_location_id')))
            ->count();

        $busyRiderIds = DispatchBatch::whereIn('status', ['loading', 'dispatched'])->pluck('rider_id')->filter()->all();

        $planner     = app(DispatchPlanner::class);
        $suggestions = $tab === 'history' ? collect() : $planner->suggestions($branchId);
        $alerts      = $tab === 'history' ? collect() : $planner->alerts($branchId);

        $pagetitle = 'Dispatch';
        return view('dispatch.index', compact('pagetitle', 'branches', 'branchId', 'zones', 'riders', 'queue', 'active', 'history', 'tab', 'awaitingPacking', 'busyRiderIds', 'suggestions', 'alerts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'orders'               => 'required|array|min:1',
            'orders.*'             => 'string',
            'rider_id'             => 'nullable|integer|exists:delivery_riders,id',
            'delivery_zone_id'     => 'nullable|integer|exists:delivery_zones,id',
            'stock_location_id'    => 'nullable|integer|exists:stock_locations,id',
            'planned_departure_at' => 'nullable|date',
            'notes'                => 'nullable|string|max:1000',
            'suggested'            => 'nullable|boolean',
        ]);

        try {
            $batch = $this->dispatch->createBatch($data['orders'], $data, (int) auth()->id());
            if ($request->boolean('suggested')) {
                $batch->update(['suggested' => true]);
            }
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('dispatch.show', $batch)->with('success', "Batch {$batch->code} created — route {$batch->route_code}.");
    }

    // ── One batch ────────────────────────────────────────────────────────────

    public function show(DispatchBatch $batch)
    {
        $batch->load(['rider', 'zone', 'branch', 'creator', 'dispatcher',
            'orders' => fn ($q) => $q->with(['parcels', 'shippingAddress', 'billingAddress', 'user', 'customer'])]);

        $events = \App\Models\Dispatch\DispatchEvent::with(['order:id,invoice_number', 'user:id,first_name,last_name'])
            ->where('dispatch_batch_id', $batch->id)->latest('id')->limit(80)->get();
        $settings = DispatchSetting::current();

        $riders = DeliveryRider::active()->orderBy('name')->get();
        $addable = $batch->isEditable()
            ? Order::with(['shippingAddress', 'billingAddress', 'user', 'customer', 'deliveryZone'])
                ->whereNull('dispatch_batch_id')->where('packing_status', 'verified')
                ->where(fn ($q) => $q->whereNull('dispatch_status')->orWhere('dispatch_status', '!=', 'courier'))
                ->whereIn('status', ['pending', 'processing'])
                ->orderByRaw('CASE WHEN delivery_zone_id = ? THEN 0 ELSE 1 END', [(int) $batch->delivery_zone_id])
                ->orderBy('created_at')->limit(200)->get()
            : collect();

        $parcelTotal  = $batch->orders->sum(fn ($o) => $o->parcels->count());
        $parcelLoaded = $batch->orders->sum(fn ($o) => $o->parcels->whereIn('status', ['loaded', 'out_for_delivery', 'delivered'])->count());

        $pagetitle = "Batch {$batch->code}";
        return view('dispatch.batch', compact('pagetitle', 'batch', 'riders', 'addable', 'parcelTotal', 'parcelLoaded', 'events', 'settings'));
    }

    public function update(Request $request, DispatchBatch $batch)
    {
        $data = $request->validate([
            'rider_id'             => 'nullable|integer|exists:delivery_riders,id',
            'planned_departure_at' => 'nullable|date',
            'notes'                => 'nullable|string|max:1000',
        ]);
        return $this->attempt(fn () => $this->dispatch->updateBatch($batch, $data), 'Batch updated.');
    }

    public function addOrders(Request $request, DispatchBatch $batch)
    {
        $data = $request->validate(['orders' => 'required|array|min:1', 'orders.*' => 'string']);
        return $this->attempt(fn () => $this->dispatch->addOrders($batch, $data['orders']), 'Orders added to the batch.');
    }

    public function removeOrder(DispatchBatch $batch, string $order)
    {
        $order = Order::findOrFail($order);
        return $this->attempt(fn () => $this->dispatch->removeOrder($batch, $order), "Order {$order->ref} taken off the batch.");
    }

    public function move(Request $request, DispatchBatch $batch, string $order)
    {
        $order = Order::findOrFail($order);
        $dir = $request->get('direction') === 'up' ? 'up' : 'down';
        return $this->attempt(fn () => $this->dispatch->moveStop($batch, $order, $dir), null);
    }

    public function autoSequence(DispatchBatch $batch)
    {
        return $this->attempt(function () use ($batch) {
            if (!$batch->isEditable()) {
                throw new RuntimeException('This batch can no longer be re-ordered.');
            }
            $this->dispatch->autoSequence($batch);
        }, 'Stops re-ordered by the zone’s area list.');
    }

    /** AJAX: scan a parcel onto the vehicle. */
    public function scan(Request $request, DispatchBatch $batch)
    {
        $request->validate(['code' => 'required|string|max:200']);
        $result = $this->dispatch->scanParcel($batch, $request->code, (int) auth()->id());

        $batch->refresh();
        $total  = $batch->parcels()->count();
        $loaded = $batch->parcels()->where('status', 'loaded')->count();

        return response()->json([
            'ok'        => $result['ok'],
            'level'     => $result['level'],
            'message'   => $result['message'],
            'parcel_id' => $result['parcel']?->id,
            'loaded'    => $loaded,
            'total'     => $total,
            'status'    => $batch->status,
        ], $result['ok'] ? 200 : 422);
    }

    public function unload(DispatchBatch $batch, OrderParcel $parcel)
    {
        return $this->attempt(fn () => $this->dispatch->unloadParcel($batch, $parcel), "Parcel {$parcel->code} marked as not loaded.");
    }

    public function dispatchBatch(Request $request, DispatchBatch $batch)
    {
        $force = $request->boolean('force');
        return $this->attempt(
            fn () => $this->dispatch->dispatch($batch, (int) auth()->id(), $force),
            "Batch {$batch->code} is out for delivery. Customers have been notified."
        );
    }

    public function stop(Request $request, DispatchBatch $batch, string $order)
    {
        $data = $request->validate([
            'outcome'        => 'required|in:delivered,failed',
            'note'           => 'nullable|string|max:500',
            'cash_collected' => 'nullable|numeric|min:0',
            'cash_method'    => 'nullable|in:cash,transfer,pos',
        ]);
        $order = Order::findOrFail($order);
        return $this->attempt(
            fn () => $this->dispatch->recordStop($batch, $order, $data['outcome'], $data['note'] ?? null, $data),
            $data['outcome'] === 'delivered' ? "Order {$order->ref} delivered." : "Order {$order->ref} marked as failed delivery."
        );
    }

    public function complete(Request $request, DispatchBatch $batch)
    {
        $data = $request->validate([
            'cash_received' => 'nullable|numeric|min:0',
            'cash_note'     => 'nullable|string|max:500',
        ]);
        try {
            $returned = $this->dispatch->complete(
                $batch,
                isset($data['cash_received']) && $data['cash_received'] !== '' ? (float) $data['cash_received'] : null,
                $data['cash_note'] ?? null,
                (int) auth()->id()
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', "Batch {$batch->code} completed." . ($returned ? " {$returned} undelivered order(s) are back in the dispatch queue." : ''));
    }

    /** Cash handed in after the run was closed. */
    public function cash(Request $request, DispatchBatch $batch)
    {
        $data = $request->validate([
            'cash_received' => 'required|numeric|min:0',
            'cash_note'     => 'nullable|string|max:500',
        ]);
        return $this->attempt(
            fn () => $this->dispatch->reconcileCash($batch, (float) $data['cash_received'], $data['cash_note'] ?? null, (int) auth()->id()),
            'Cash recorded.'
        );
    }

    // ── Performance & settings ───────────────────────────────────────────────

    public function performance(Request $request)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(6)->startOfDay();
        $to   = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        $branchId = $request->integer('branch') ?: null;
        $branches = StockLocation::active()->orderBy('name')->get(['id', 'name']);
        $report   = app(DispatchPlanner::class)->performance($from, $to, $branchId);

        $pagetitle = 'Rider performance';
        return view('dispatch.performance', compact('pagetitle', 'report', 'from', 'to', 'branchId', 'branches'));
    }

    public function settings()
    {
        $settings  = DispatchSetting::query()->first() ?? new DispatchSetting(DispatchSetting::defaults());
        $pagetitle = 'Dispatch settings';
        return view('dispatch.settings', compact('settings', 'pagetitle'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'max_code_attempts'     => 'required|integer|min:1|max:20',
            'default_capacity'      => 'required|integer|min:1|max:500',
            'max_stops_per_run'     => 'required|integer|min:1|max:100',
            'default_promise_hour'  => 'required|integer|min:6|max:23',
            'pack_within_minutes'   => 'required|integer|min:10|max:2880',
            'at_risk_minutes'       => 'required|integer|min:10|max:1440',
            'loading_stuck_minutes' => 'required|integer|min:10|max:1440',
            'run_overdue_minutes'   => 'required|integer|min:30|max:2880',
        ]);
        $data['require_delivery_code'] = $request->boolean('require_delivery_code');
        $data['allow_photo_fallback']  = $request->boolean('allow_photo_fallback');

        $s = DispatchSetting::query()->first() ?? new DispatchSetting();
        $s->fill($data)->save();
        DispatchSetting::forget();

        return back()->with('success', 'Dispatch settings saved.');
    }

    public function cancel(DispatchBatch $batch)
    {
        try {
            $this->dispatch->cancel($batch);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return redirect()->route('dispatch.index')->with('success', "Batch {$batch->code} cancelled — its orders are back in the queue.");
    }

    // ── Documents ────────────────────────────────────────────────────────────

    public function manifest(DispatchBatch $batch)
    {
        $batch->load(['rider', 'zone', 'branch', 'creator', 'dispatcher',
            'orders' => fn ($q) => $q->with(['parcels', 'shippingAddress', 'billingAddress', 'user', 'customer'])]);

        $store = ['name' => optional(\App\Models\StoreSetting::query()->first())->store_name ?: config('app.name', 'GozakMart')];
        $pdf = Pdf::loadView('dispatch.pdf.manifest', compact('batch', 'store'))->setPaper('a4', 'landscape')->setOption('isFontSubsettingEnabled', true);
        return $pdf->stream("manifest-{$batch->code}.pdf");
    }

    public function labels(Request $request, DispatchBatch $batch, PackingController $packing)
    {
        $parcels = OrderParcel::with(['order.shippingAddress', 'order.billingAddress', 'order.user', 'order.customer', 'order.deliveryZone', 'order.branch'])
            ->where('dispatch_batch_id', $batch->id)
            ->get()
            ->each(fn ($p) => $p->order?->setRelation('dispatchBatch', $batch))
            ->sortBy(fn ($p) => sprintf('%05d-%03d', $p->order?->stop_sequence ?? 99999, $p->sequence))
            ->values();

        if ($parcels->isEmpty()) {
            return back()->with('error', 'This batch has no parcels.');
        }
        OrderParcel::whereIn('id', $parcels->pluck('id'))->whereNull('labelled_at')->update(['labelled_at' => now()]);

        return $packing->renderLabels($parcels, $request->get('size'), "labels-{$batch->code}.pdf");
    }

    /** AJAX: what is this parcel? (for a stray bag found on the floor) */
    public function lookup(Request $request)
    {
        $code   = $this->dispatch->parseScan((string) $request->get('code', ''));
        $parcel = OrderParcel::with(['order.shippingAddress', 'order.billingAddress', 'batch.rider'])->where('code', $code)->first();
        if (!$parcel) {
            return response()->json(['found' => false, 'message' => "No parcel with code {$code}."], 404);
        }
        $o = $parcel->order;
        return response()->json([
            'found'    => true,
            'code'     => $parcel->code,
            'parcel'   => "{$parcel->sequence} of " . ($o?->package_count ?: $o?->parcels()->count()),
            'status'   => str_replace('_', ' ', $parcel->status),
            'order'    => $o?->ref,
            'order_url'=> $o ? route('adminorders.show', $o->id) : null,
            'area'     => $o ? \App\Support\DeliveryInfo::area($o) : null,
            'batch'    => $parcel->batch?->code,
            'route'    => $parcel->batch?->route_code,
            'rider'    => $parcel->batch?->rider?->name,
            'batch_url'=> $parcel->batch ? route('dispatch.show', $parcel->batch) : null,
        ]);
    }

    private function attempt(callable $fn, ?string $success)
    {
        try {
            $fn();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return $success ? back()->with('success', $success) : back();
    }
}
