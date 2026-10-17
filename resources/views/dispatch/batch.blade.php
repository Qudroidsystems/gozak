@extends('layouts.master')

@section('title', 'Batch ' . $batch->code)

@section('content')
@php
    use App\Support\DeliveryInfo as D;
    $canManage = auth()->user()->can('Manage dispatch');
    $editable  = $canManage && $batch->isEditable();
    $out       = $batch->status === 'dispatched';
    $openStops = $batch->orders->where('dispatch_status', 'out_for_delivery')->count();
    $collect   = $batch->orders->sum(fn ($o) => D::amountToCollect($o));
    $pct       = $parcelTotal ? round($parcelLoaded / $parcelTotal * 100) : 0;
    $stopBadge = ['assigned' => 'secondary', 'out_for_delivery' => 'primary', 'delivered' => 'success', 'failed' => 'danger'];
    $stopText  = ['assigned' => 'Assigned', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'failed' => 'Failed'];
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('dispatch._nav', [
        'title'     => 'Batch ' . $batch->code,
        'subtitle'  => 'Route ' . $batch->route_code . ($batch->zone ? ' · ' . $batch->zone->name : '') . ($batch->branch ? ' · ' . $batch->branch->name : '') . ' · created ' . $batch->created_at->format('j M H:i') . ($batch->creator ? ' by ' . $batch->creator->full_name : ''),
        'back'      => route('dispatch.index'),
        'backLabel' => 'Dispatch board',
    ])

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-cb.stat label="Route" :value="$batch->route_code" icon="ri-route-line" accent="violet" :hint="$batch->statusLabel()" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Stops" :value="$batch->orders->count()" icon="ri-map-pin-line" accent="sky" :hint="$out ? $batch->orders->where('dispatch_status', 'delivered')->count() . ' delivered' : null" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Parcels loaded" :value="$parcelLoaded . ' / ' . $parcelTotal" icon="ri-archive-2-line" :accent="$parcelLoaded === $parcelTotal && $parcelTotal ? 'green' : 'amber'" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Cash to collect" :value="'₦' . number_format($collect)" icon="ri-money-dollar-circle-line" accent="rose" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            {{-- Rider & actions --}}
            <x-cb.card title="Rider & run" icon="ri-e-bike-2-line">
                @if($canManage && !in_array($batch->status, ['completed', 'cancelled'], true))
                    <form method="POST" action="{{ route('dispatch.update', $batch) }}">
                        @csrf @method('PUT')
                        <label class="form-label small mb-1">Rider</label>
                        <select name="rider_id" class="form-select form-select-sm mb-2" @disabled($out)>
                            <option value="">— choose —</option>
                            @foreach($riders as $r)
                                <option value="{{ $r->id }}" @selected($batch->rider_id == $r->id)>{{ $r->name }} · {{ $r->vehicleLabel() }}{{ $r->capacity ? ' · max ' . $r->capacity : '' }}</option>
                            @endforeach
                        </select>
                        @if($out)<input type="hidden" name="rider_id" value="{{ $batch->rider_id }}">@endif
                        <label class="form-label small mb-1">Leaves at</label>
                        <input type="datetime-local" name="planned_departure_at" class="form-control form-control-sm mb-2" value="{{ $batch->planned_departure_at?->format('Y-m-d\TH:i') }}">
                        <label class="form-label small mb-1">Notes for the rider</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm mb-2" maxlength="1000">{{ $batch->notes }}</textarea>
                        <button class="btn btn-sm btn-outline-primary w-100">Save</button>
                    </form>
                @else
                    <div class="fw-semibold">{{ $batch->rider?->name ?? '—' }}</div>
                    <div class="small text-muted">{{ $batch->rider?->phone }} · {{ $batch->rider?->vehicleLabel() }}</div>
                    @if($batch->notes)<div class="small mt-2">{{ $batch->notes }}</div>@endif
                @endif

                @if($batch->rider?->capacity && $parcelTotal > $batch->rider->capacity)
                    <div class="alert alert-warning small py-2 mt-2 mb-0">{{ $parcelTotal }} parcels is over {{ $batch->rider->name }}'s capacity of {{ $batch->rider->capacity }}.</div>
                @endif

                <hr>
                <div class="small text-muted mb-1">Documents</div>
                <div class="d-grid gap-2">
                    <a href="{{ route('dispatch.manifest', $batch) }}" target="_blank" class="btn btn-sm btn-dark"><i class="ri-printer-line"></i> Dispatch manifest</a>
                    <div class="btn-group">
                        <a href="{{ route('dispatch.labels', $batch) }}" target="_blank" class="btn btn-sm btn-outline-dark"><i class="ri-barcode-line"></i> All parcel labels (4×6")</a>
                        <a href="{{ route('dispatch.labels', [$batch, 'size' => 'compact']) }}" target="_blank" class="btn btn-sm btn-outline-dark">Compact</a>
                    </div>
                </div>
                <div class="small text-muted mt-1">Reprint labels after creating the batch — they now carry the route code and stop number.</div>

                @if($canManage)
                    <hr>
                    <div class="d-grid gap-2">
                        @if($batch->isEditable())
                            <form method="POST" action="{{ route('dispatch.dispatch', $batch) }}" class="d-grid gap-1" id="dispatchForm">
                                @csrf
                                <button class="btn btn-success" @disabled(!$batch->rider_id || !$batch->orders->count())>
                                    <i class="ri-send-plane-2-line"></i> Hand over &amp; dispatch
                                </button>
                                @if($parcelLoaded < $parcelTotal)
                                    <div class="form-check small">
                                        <input class="form-check-input" type="checkbox" name="force" value="1" id="forceDispatch">
                                        <label class="form-check-label text-danger" for="forceDispatch">Dispatch without scanning all parcels ({{ $parcelTotal - $parcelLoaded }} missing)</label>
                                    </div>
                                @endif
                                @unless($batch->rider_id)<div class="small text-muted">Assign a rider first.</div>@endunless
                            </form>
                            <form method="POST" action="{{ route('dispatch.cancel', $batch) }}" onsubmit="return confirm('Cancel this batch? Its orders go back to the queue.')">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger w-100"><i class="ri-close-circle-line"></i> Cancel batch</button>
                            </form>
                        @elseif($out)
                            @php
                                $inHand  = $batch->orders->where('cash_method', 'cash')->sum('cash_collected');
                                $inBank  = $batch->orders->whereIn('cash_method', ['transfer', 'pos'])->sum('cash_collected');
                            @endphp
                            <form method="POST" action="{{ route('dispatch.complete', $batch) }}">
                                @csrf
                                @if((float) $batch->cash_expected > 0 || $inHand > 0)
                                    <div class="small mb-1">Rider reported <b>₦{{ number_format($inHand, 2) }}</b> cash@if($inBank > 0) · ₦{{ number_format($inBank, 2) }} transfer/POS@endif</div>
                                    <label class="form-label small mb-1">Cash handed in (₦)</label>
                                    <input type="number" step="0.01" min="0" name="cash_received" class="form-control form-control-sm mb-1" value="{{ number_format($inHand, 2, '.', '') }}">
                                    <input type="text" name="cash_note" maxlength="500" class="form-control form-control-sm mb-2" placeholder="Note (e.g. ₦500 short — change given)">
                                @endif
                                <button class="btn btn-primary w-100" @disabled($openStops > 0)><i class="ri-flag-2-line"></i> Close the run</button>
                                @if($openStops)<div class="small text-muted mt-1">{{ $openStops }} stop(s) still open.</div>@endif
                            </form>
                        @endif
                    </div>
                @endif

                @if($batch->status === 'completed')
                    @php
                        $inHand = $batch->orders->where('cash_method', 'cash')->sum('cash_collected');
                        $var = $batch->cash_received !== null ? (float) $batch->cash_received - $inHand : null;
                    @endphp
                    <hr>
                    <div class="small text-muted mb-1">Cash</div>
                    <table class="table table-sm small mb-2">
                        <tr><td>Expected (COD at dispatch)</td><td class="text-end">₦{{ number_format($batch->cash_expected, 2) }}</td></tr>
                        <tr><td>Rider reported (cash)</td><td class="text-end">₦{{ number_format($inHand, 2) }}</td></tr>
                        <tr><td>Handed in</td><td class="text-end fw-semibold">{{ $batch->cash_received !== null ? '₦' . number_format($batch->cash_received, 2) : '—' }}</td></tr>
                        @if($var !== null)<tr><td>Difference</td><td class="text-end fw-bold {{ $var < -0.009 ? 'text-danger' : 'text-success' }}">{{ $var < 0 ? '−' : '' }}₦{{ number_format(abs($var), 2) }}</td></tr>@endif
                    </table>
                    @if($batch->cash_note)<div class="small text-muted">{{ $batch->cash_note }}</div>@endif
                    @if($batch->cash_received === null && $canManage && $inHand > 0)
                        <form method="POST" action="{{ route('dispatch.cash', $batch) }}" class="d-flex gap-2">
                            @csrf
                            <input type="number" step="0.01" min="0" name="cash_received" class="form-control form-control-sm" value="{{ number_format($inHand, 2, '.', '') }}">
                            <button class="btn btn-sm btn-primary text-nowrap">Record cash</button>
                        </form>
                    @endif
                @endif

                <hr>
                <div class="small">
                    @if($batch->dispatched_at)<div><span class="text-muted">Dispatched:</span> {{ $batch->dispatched_at->format('j M H:i') }}@if($batch->dispatcher) by {{ $batch->dispatcher->full_name }}@endif</div>@endif
                    @if($batch->completed_at)<div><span class="text-muted">Closed:</span> {{ $batch->completed_at->format('j M H:i') }}</div>@endif
                </div>
            </x-cb.card>
        </div>

        <div class="col-xl-8">
            {{-- Load by scan --}}
            @if($editable)
                <x-cb.card title="Load the vehicle" icon="ri-qr-scan-2-line">
                    <p class="small text-muted mb-2">Scan each parcel label as it goes onto the vehicle. Parcels from another batch are rejected.</p>
                    <form id="scanForm" class="d-flex gap-2" autocomplete="off">
                        <input type="text" id="scanInput" class="form-control form-control-lg font-monospace" placeholder="Scan parcel barcode / QR…" autofocus>
                        <button class="btn btn-dark btn-lg px-4">Load</button>
                    </form>
                    <div class="progress-cell mt-3">
                        <div class="progress-track" style="height:10px;"><div class="progress-fill" id="loadBar" style="width: {{ $pct }}%"></div></div>
                        <div class="progress-meta"><span id="loadText">{{ $parcelLoaded }} of {{ $parcelTotal }} parcels loaded</span><span id="loadPct">{{ $pct }}%</span></div>
                    </div>
                    <div id="scanFlash" class="mt-3"></div>
                    <ul id="scanLog" class="list-unstyled small mb-0 mt-2" style="max-height: 160px; overflow:auto;"></ul>
                </x-cb.card>
            @endif

            {{-- Stops --}}
            <div class="cb-card">
                <div class="cb-card-header">
                    <h5><i class="ri-map-pin-line"></i> Stops <span class="cb-count">{{ $batch->orders->count() }}</span></h5>
                    @if($editable)
                        <form method="POST" action="{{ route('dispatch.sequence', $batch) }}">@csrf
                            <button class="btn btn-sm btn-outline-secondary" title="Sort by the zone's area list"><i class="ri-sort-asc"></i> Auto-order stops</button>
                        </form>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light small"><tr><th style="width:70px;">Stop</th><th>Order &amp; customer</th><th>Area</th><th>Parcels</th><th class="text-end">Collect</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @forelse($batch->orders as $o)
                            @php $amt = D::amountToCollect($o); @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="fs-4 fw-bold">{{ $o->stop_sequence }}</span>
                                        @if($editable)
                                            <div class="d-flex flex-column">
                                                <form method="POST" action="{{ route('dispatch.orders.move', [$batch, $o->id]) }}">@csrf<input type="hidden" name="direction" value="up"><button class="btn btn-link btn-sm p-0 lh-1" @disabled($loop->first)><i class="ri-arrow-up-s-line"></i></button></form>
                                                <form method="POST" action="{{ route('dispatch.orders.move', [$batch, $o->id]) }}">@csrf<input type="hidden" name="direction" value="down"><button class="btn btn-link btn-sm p-0 lh-1" @disabled($loop->last)><i class="ri-arrow-down-s-line"></i></button></form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('adminorders.show', $o->id) }}" class="fw-semibold">{{ $o->ref }}</a>
                                    <div>{{ D::recipient($o) }}</div>
                                    <div class="small text-muted">{{ D::phone($o) }}</div>
                                </td>
                                <td class="small">
                                    <div class="fw-semibold">{{ D::area($o) }}</div>
                                    <div class="text-muted">{{ \Illuminate\Support\Str::limit(D::shortAddress($o, 70), 70) }}</div>
                                </td>
                                <td class="small">
                                    @foreach($o->parcels as $p)
                                        @php $isLoaded = in_array($p->status, ['loaded', 'out_for_delivery', 'delivered'], true); @endphp
                                        <div class="text-nowrap">
                                            <span class="badge parcel-chip {{ $isLoaded ? 'bg-success' : 'bg-light text-dark border' }}" data-parcel="{{ $p->id }}">
                                                <i class="{{ $isLoaded ? 'ri-check-line' : 'ri-checkbox-blank-line' }}"></i> {{ $p->code }}
                                            </span>
                                            @if($editable && $p->status === 'loaded')
                                                <form method="POST" action="{{ route('dispatch.unload', [$batch, $p]) }}" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0 text-muted" title="Mark as not loaded"><i class="ri-arrow-go-back-line"></i></button></form>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td class="text-end text-nowrap">{!! $amt > 0 ? '<b>₦' . number_format($amt, 2) . '</b>' : '<span class="badge bg-success-subtle text-success">Paid</span>' !!}</td>
                                <td class="small">
                                    <span class="badge bg-{{ $stopBadge[$o->dispatch_status] ?? 'secondary' }}">{{ $stopText[$o->dispatch_status] ?? $o->dispatch_status }}</span>
                                    @if($o->dispatch_status === 'delivered')
                                        @php $mLabel = ['code' => 'Code ✓', 'photo' => 'Photo', 'admin' => 'By admin', 'none' => 'Rider']; @endphp
                                        <span class="badge bg-{{ $o->delivery_method === 'code' ? 'success' : 'warning' }}-subtle text-{{ $o->delivery_method === 'code' ? 'success' : 'warning' }}">{{ $mLabel[$o->delivery_method] ?? $o->delivery_method }}</span>
                                        @if($o->delivery_receiver)<div class="text-muted">Received by {{ $o->delivery_receiver }}</div>@endif
                                        @if($o->delivery_proof)<div><a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($o->delivery_proof) }}" target="_blank"><i class="ri-image-line"></i> Proof photo</a></div>@endif
                                        @if($o->delivered_lat)<div><a href="https://www.google.com/maps?q={{ $o->delivered_lat }},{{ $o->delivered_lng }}" target="_blank"><i class="ri-map-pin-line"></i> Where</a></div>@endif
                                        @if($o->cash_collected)<div>₦{{ number_format($o->cash_collected, 2) }} · {{ strtoupper($o->cash_method ?? '') }}</div>@endif
                                        <div class="text-muted">{{ $o->delivered_at?->format('H:i') }}</div>
                                    @elseif($o->dispatch_status === 'out_for_delivery' && $o->delivery_code && $canManage)
                                        <div class="text-muted mt-1" title="Read this to customers who don't use the app">Code <span class="font-monospace fw-bold text-dark">{{ $o->delivery_code }}</span>@if($o->delivery_code_attempts) · <span class="text-danger">{{ $o->delivery_code_attempts }} wrong</span>@endif</div>
                                    @endif
                                    @if($o->dispatch_note)<div class="text-muted">{{ $o->dispatch_note }}</div>@endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @if($editable)
                                        <form method="POST" action="{{ route('dispatch.orders.remove', [$batch, $o->id]) }}" onsubmit="return confirm('Take {{ $o->ref }} off this batch?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Remove from batch"><i class="ri-close-line"></i></button>
                                        </form>
                                    @elseif($out && $canManage && $o->dispatch_status === 'out_for_delivery')
                                        <button type="button" class="btn btn-sm btn-success js-deliver" data-url="{{ route('dispatch.orders.stop', [$batch, $o->id]) }}" data-ref="{{ $o->ref }}" data-due="{{ $amt }}"><i class="ri-check-double-line"></i> Delivered</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger js-fail" data-url="{{ route('dispatch.orders.stop', [$batch, $o->id]) }}" data-ref="{{ $o->ref }}">Failed</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No stops on this batch.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Add more orders --}}
            @if($editable && $addable->count())
                <x-cb.card title="Add more orders" icon="ri-add-circle-line" :count="$addable->count()" :flush="true">
                    <form method="POST" action="{{ route('dispatch.orders.add', $batch) }}">
                        @csrf
                        <div class="table-responsive" style="max-height: 320px;">
                            <table class="table table-sm table-hover align-middle mb-0 small">
                                <tbody>
                                @foreach($addable as $o)
                                    <tr>
                                        <td style="width:34px;"><input type="checkbox" class="form-check-input" name="orders[]" value="{{ $o->id }}"></td>
                                        <td class="fw-semibold">{{ $o->ref }}</td>
                                        <td>{{ D::recipient($o) }}</td>
                                        <td>{{ D::area($o) }}</td>
                                        <td>@if($o->deliveryZone)<span class="badge {{ $o->delivery_zone_id == $batch->delivery_zone_id ? 'bg-success' : 'bg-secondary' }}">{{ $o->deliveryZone->code }}</span>@endif</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top text-end"><button class="btn btn-sm btn-primary">Add selected to batch</button></div>
                    </form>
                </x-cb.card>
            @endif

            @if($events->count())
                <x-cb.card title="Activity" icon="ri-history-line" :count="$events->count()" :flush="true">
                    <div class="list-group list-group-flush small" style="max-height: 360px; overflow:auto;">
                        @php $evIcon = ['assigned' => 'ri-add-line', 'dispatched' => 'ri-send-plane-2-line', 'delivered' => 'ri-checkbox-circle-line text-success', 'failed' => 'ri-close-circle-line text-danger', 'code_failed' => 'ri-lock-password-line text-danger', 'called' => 'ri-phone-line', 'returned' => 'ri-arrow-go-back-line text-warning', 'completed' => 'ri-flag-2-line', 'cash_in' => 'ri-hand-coin-line text-success']; @endphp
                        @foreach($events as $e)
                            <div class="list-group-item d-flex gap-2">
                                <i class="{{ $evIcon[$e->event] ?? 'ri-information-line' }}"></i>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold">{{ str_replace('_', ' ', ucfirst($e->event)) }}</span>
                                    @if($e->order) · {{ $e->order->ref }}@endif
                                    @if($e->note)<span class="text-muted"> — {{ $e->note }}</span>@endif
                                    @if($e->lat)<a class="ms-1" target="_blank" href="https://www.google.com/maps?q={{ $e->lat }},{{ $e->lng }}"><i class="ri-map-pin-line"></i></a>@endif
                                </div>
                                <span class="text-muted text-nowrap">{{ $e->created_at?->format('j M H:i') }}@if($e->user) · {{ $e->user->full_name }}@endif</span>
                            </div>
                        @endforeach
                    </div>
                </x-cb.card>
            @endif
        </div>
    </div>

</div>
</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Admin confirms a delivery (e.g. rider phoned it in). Ask for cash on pay-on-delivery orders.
    document.querySelectorAll('.js-deliver').forEach(b => b.addEventListener('click', () => {
        const due = parseFloat(b.dataset.due || '0');
        const post = (fields) => {
            const f = document.createElement('form');
            f.method = 'POST'; f.action = b.dataset.url;
            f.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="outcome" value="delivered">`;
            Object.entries(fields).forEach(([k, v]) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = v; f.appendChild(i); });
            document.body.appendChild(f); f.submit();
        };
        Swal.fire({
            title: 'Mark ' + b.dataset.ref + ' delivered?',
            html: due > 0
                ? `<div class="text-start small">Pay on delivery: ₦${due.toLocaleString()}</div>
                   <input id="swCash" type="number" step="0.01" class="swal2-input" value="${due.toFixed(2)}" placeholder="Amount collected">
                   <select id="swMethod" class="swal2-select"><option value="cash">Cash</option><option value="transfer">Transfer</option><option value="pos">POS</option></select>
                   <input id="swNote" class="swal2-input" placeholder="Note (who confirmed?)">`
                : `<input id="swNote" class="swal2-input" placeholder="Note (who confirmed?)">`,
            showCancelButton: true, confirmButtonText: 'Delivered', confirmButtonColor: '#16a34a',
            preConfirm: () => ({
                cash_collected: due > 0 ? document.getElementById('swCash').value : '',
                cash_method: due > 0 ? document.getElementById('swMethod').value : '',
                note: document.getElementById('swNote').value || 'Confirmed by dispatch',
            }),
        }).then(r => { if (r.isConfirmed) post(r.value); });
    }));

    // Failed delivery → ask for the reason.
    document.querySelectorAll('.js-fail').forEach(b => b.addEventListener('click', () => {
        Swal.fire({
            title: 'Delivery failed — ' + b.dataset.ref,
            input: 'select',
            inputOptions: { 'Customer not available': 'Customer not available', 'Phone unreachable': 'Phone unreachable', 'Wrong / incomplete address': 'Wrong / incomplete address', 'Customer refused the order': 'Customer refused the order', 'Could not pay on delivery': 'Could not pay on delivery', 'Rescheduled by customer': 'Rescheduled by customer', 'Other': 'Other' },
            showCancelButton: true, confirmButtonText: 'Mark as failed', confirmButtonColor: '#dc3545',
        }).then(r => {
            if (!r.isConfirmed) return;
            const f = document.createElement('form');
            f.method = 'POST'; f.action = b.dataset.url;
            f.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="outcome" value="failed"><input type="hidden" name="note">`;
            f.querySelector('[name=note]').value = r.value;
            document.body.appendChild(f); f.submit();
        });
    }));

    const form = document.getElementById('scanForm');
    if (!form) return;
    const input = document.getElementById('scanInput'), flash = document.getElementById('scanFlash'), log = document.getElementById('scanLog');
    const bar = document.getElementById('loadBar'), text = document.getElementById('loadText'), pctEl = document.getElementById('loadPct');
    let audio;
    function beep(ok) {
        try {
            audio = audio || new (window.AudioContext || window.webkitAudioContext)();
            const o = audio.createOscillator(), g = audio.createGain();
            o.type = ok ? 'sine' : 'square'; o.frequency.value = ok ? 1200 : 220;
            o.connect(g); g.connect(audio.destination); g.gain.value = .15;
            o.start(); o.stop(audio.currentTime + (ok ? .12 : .5));
        } catch (e) {}
    }
    const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    form.addEventListener('submit', e => {
        e.preventDefault();
        const code = input.value.trim();
        if (!code) return;
        input.value = '';
        fetch(`{{ route('dispatch.scan', $batch) }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ code })
        }).then(r => r.json()).then(d => {
            const cls = { success: 'success', info: 'info', danger: 'danger' }[d.level] || 'secondary';
            flash.innerHTML = `<div class="alert alert-${cls} mb-0 ${cls === 'danger' ? 'fs-5 fw-bold' : ''}">${esc(d.message || 'Error')}</div>`;
            log.insertAdjacentHTML('afterbegin', `<li class="text-${cls}"><i class="ri-${cls === 'danger' ? 'close' : 'check'}-line"></i> ${new Date().toLocaleTimeString()} · ${esc(d.message || code)}</li>`);
            beep(d.ok);
            if (d.parcel_id && d.ok) {
                const chip = document.querySelector(`.parcel-chip[data-parcel="${d.parcel_id}"]`);
                if (chip) { chip.className = 'badge parcel-chip bg-success'; chip.querySelector('i').className = 'ri-check-line'; }
            }
            if (typeof d.total === 'number') {
                const pct = d.total ? Math.round(d.loaded / d.total * 100) : 0;
                bar.style.width = pct + '%'; pctEl.textContent = pct + '%';
                text.textContent = `${d.loaded} of ${d.total} parcels loaded`;
                if (d.total && d.loaded === d.total) flash.insertAdjacentHTML('beforeend', '<div class="alert alert-success mt-2 mb-0"><b>All parcels loaded.</b> Print the manifest and hand over.</div>');
            }
        }).catch(() => { flash.innerHTML = '<div class="alert alert-danger mb-0">Network error — scan again.</div>'; beep(false); })
          .finally(() => input.focus());
    });
})();
</script>
@endpush
