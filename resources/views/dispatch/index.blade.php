@extends('layouts.master')

@section('title', 'Dispatch')

@section('content')
@php
    use App\Support\DeliveryInfo as D;
    $readyCount  = $queue->flatten()->where('packing_status', 'verified')->count();
    $uncheckedCount = $queue->flatten()->where('packing_status', 'packed')->count();
    $canManage   = auth()->user()->can('Manage dispatch');
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('dispatch._nav')

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-cb.stat label="Ready for a rider" :value="$readyCount" icon="ri-checkbox-circle-line" accent="green" hint="packed & checked" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Packed, awaiting check" :value="$uncheckedCount" icon="ri-shield-check-line" accent="amber" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Waiting to be packed" :value="$awaitingPacking" icon="ri-shopping-basket-2-line" accent="sky" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Runs in progress" :value="$active->count()" icon="ri-truck-line" accent="violet" :hint="$active->where('status', 'dispatched')->count() . ' on the road'" /></div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <ul class="nav nav-pills gap-1">
            <li class="nav-item"><a class="nav-link {{ $tab !== 'history' ? 'active' : '' }}" href="{{ route('dispatch.index', array_filter(['branch' => $branchId])) }}"><i class="ri-layout-column-line me-1"></i>Board</a></li>
            <li class="nav-item"><a class="nav-link {{ $tab === 'history' ? 'active' : '' }}" href="{{ route('dispatch.index', array_filter(['branch' => $branchId, 'tab' => 'history'])) }}"><i class="ri-history-line me-1"></i>Completed runs</a></li>
        </ul>
        @if($branches->count() > 1)
            <form method="GET" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <span class="small text-muted">Branch</span>
                <select name="branch" class="cb-select" onchange="this.form.submit()">
                    <option value="">All branches</option>
                    @foreach($branches as $b)<option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->name }}</option>@endforeach
                </select>
            </form>
        @endif
    </div>

@if($tab === 'history')
    <x-cb.card title="Completed & cancelled runs" icon="ri-history-line" :flush="true">
        <div class="table-responsive">
            <table class="cb-table">
                <thead><tr><th>Batch</th><th>Route</th><th>Rider</th><th class="num">Stops</th><th class="num">Parcels</th><th>Dispatched</th><th>Closed</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($history as $b)
                    <tr>
                        <td><a href="{{ route('dispatch.show', $b) }}" class="fw-semibold font-monospace">{{ $b->code }}</a></td>
                        <td><span class="badge bg-dark">{{ $b->route_code }}</span> <span class="small text-muted">{{ $b->zone?->name }}</span></td>
                        <td>{{ $b->rider?->name ?? '—' }}</td>
                        <td class="num">{{ $b->orders_count }}</td>
                        <td class="num">{{ $b->parcels_count }}</td>
                        <td class="small">{{ $b->dispatched_at?->format('j M H:i') ?? '—' }}</td>
                        <td class="small">{{ ($b->completed_at ?? $b->updated_at)->format('j M H:i') }}</td>
                        <td><span class="badge bg-{{ $b->statusBadge() }}">{{ $b->statusLabel() }}</span></td>
                        <td><a href="{{ route('dispatch.manifest', $b) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="ri-printer-line"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No completed runs yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($history->hasPages())<div class="p-3">{{ $history->links() }}</div>@endif
    </x-cb.card>
@else
    @if($alerts->count())
        <div class="cb-card mb-3">
            <div class="cb-card-header">
                <h5><i class="ri-alarm-warning-line"></i> Needs attention <span class="cb-count" style="background:#e11d48;">{{ $alerts->count() }}</span></h5>
            </div>
            <div class="list-group list-group-flush">
                @foreach($alerts as $a)
                    <a href="{{ $a['url'] ?? '#' }}" class="list-group-item list-group-item-action d-flex gap-3 align-items-start py-2">
                        <i class="{{ $a['icon'] }} fs-4 text-{{ $a['level'] }}"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $a['title'] }}</div>
                            <div class="small text-muted">{{ $a['detail'] }}</div>
                        </div>
                        <span class="badge bg-{{ $a['level'] }}-subtle text-{{ $a['level'] }} align-self-center">{{ ['danger' => 'urgent', 'warning' => 'soon', 'info' => 'to do'][$a['level']] ?? '' }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3">
        {{-- Ready queue --}}
        <div class="col-xl-7">
            @if($canManage && $suggestions->count())
                <div class="cb-card mb-3">
                    <div class="cb-card-header">
                        <h5><i class="ri-magic-line"></i> Suggested runs <span class="cb-count">{{ $suggestions->count() }}</span></h5>
                        <span class="small text-muted">By zone, promised time, route order and rider capacity</span>
                    </div>
                    <div class="p-3">
                        @foreach($suggestions as $sg)
                            @php $lvl = ['overdue' => 'danger', 'urgent' => 'warning', 'normal' => 'secondary'][$sg['level']]; @endphp
                            <form method="POST" action="{{ route('dispatch.store') }}" class="border rounded p-3 mb-2 {{ $sg['level'] === 'overdue' ? 'border-danger' : '' }}">
                                @csrf
                                <input type="hidden" name="suggested" value="1">
                                @if($sg['zone'])<input type="hidden" name="delivery_zone_id" value="{{ $sg['zone']->id }}">@endif
                                @foreach($sg['orders'] as $o)<input type="hidden" name="orders[]" value="{{ $o->id }}">@endforeach
                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                    <div>
                                        <span class="badge" style="background: {{ $sg['zone']?->color ?: '#334155' }}">{{ $sg['zone']?->code ?? 'GEN' }}</span>
                                        <span class="fw-semibold">{{ $sg['orders']->count() }} stops · {{ $sg['parcels'] }} parcels</span>
                                        @if($sg['level'] !== 'normal')<span class="badge bg-{{ $lvl }} ms-1">{{ $sg['level'] }}</span>@endif
                                        @if($sg['cod'] > 0)<span class="badge bg-warning-subtle text-warning ms-1">COD ₦{{ number_format($sg['cod']) }}</span>@endif
                                        <div class="small text-muted mt-1">{{ $sg['why'] }}</div>
                                        <div class="small mt-1">
                                            @foreach($sg['orders'] as $o)<span class="me-2"><b>{{ $loop->iteration }}.</b> {{ $o->ref }} <span class="text-muted">{{ \Illuminate\Support\Str::limit(\App\Support\DeliveryInfo::shortAddress($o, 30), 30) }}</span></span>@endforeach
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <select name="rider_id" class="form-select form-select-sm" style="min-width: 150px;">
                                            <option value="">Assign later</option>
                                            @foreach($riders as $r)
                                                <option value="{{ $r->id }}" @selected($sg['rider']?->id === $r->id)>{{ $r->name }}{{ in_array($r->id, $busyRiderIds) ? ' (busy)' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-success text-nowrap"><i class="ri-check-line"></i> Create run</button>
                                    </div>
                                </div>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('dispatch.store') }}" id="batchForm">
                @csrf
                @forelse($queue as $zoneId => $orders)
                    @php $zone = $zoneId ? $zones->get($zoneId) : null; @endphp
                    <div class="cb-card mb-3">
                        <div class="cb-card-header">
                            <h5>
                                <span class="badge" style="background: {{ $zone?->color ?: '#334155' }}">{{ $zone?->code ?? '—' }}</span>
                                {{ $zone?->name ?? 'No zone matched' }}
                                <span class="cb-count">{{ $orders->count() }}</span>
                            </h5>
                            @if($canManage)
                                <button type="button" class="btn btn-sm btn-outline-secondary js-select-zone" data-zone="{{ $zoneId }}">Select all ready</button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <tbody>
                                @foreach($orders as $o)
                                    @php $ready = $o->packing_status === 'verified'; $collect = D::amountToCollect($o); @endphp
                                    <tr class="{{ $ready ? '' : 'table-warning' }}">
                                        <td style="width:34px;">
                                            @if($canManage)
                                                <input type="checkbox" class="form-check-input js-order" name="orders[]" value="{{ $o->id }}"
                                                       data-zone="{{ $zoneId }}" data-parcels="{{ $o->parcels->count() }}" @disabled(!$ready)>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('adminorders.show', $o->id) }}#packing" class="fw-semibold">{{ $o->ref }}</a>
                                            @unless($ready)<div><span class="badge bg-warning text-dark">needs check</span></div>@endunless
                                            @if($o->dispatch_status === 'returned')<div><span class="badge bg-danger-subtle text-danger" title="{{ $o->dispatch_note }}">re-delivery</span></div>@endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ D::recipient($o) }}</div>
                                            <div class="text-muted">{{ D::area($o) }}</div>
                                        </td>
                                        <td class="text-muted d-none d-md-table-cell">{{ \Illuminate\Support\Str::limit(D::shortAddress($o, 60), 60) }}</td>
                                        <td class="text-nowrap"><i class="ri-archive-2-line"></i> {{ $o->parcels->count() }}</td>
                                        <td class="text-nowrap">
                                            @if($o->delivery_date)
                                                <span class="{{ $o->delivery_date->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $o->delivery_date->format('j M') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">{!! $collect > 0 ? '<span class="badge bg-warning-subtle text-warning">COD ₦' . number_format($collect) . '</span>' : '<span class="badge bg-success-subtle text-success">Paid</span>' !!}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <x-cb.card>
                        <div class="text-center text-muted py-4">
                            <i class="ri-inbox-line fs-1 d-block mb-2"></i>
                            Nothing is waiting for a rider. Orders appear here once they are packed on the order page.
                        </div>
                    </x-cb.card>
                @endforelse

                @if($canManage && $readyCount)
                    <div class="cb-card p-3 position-sticky" style="bottom: 12px; z-index: 5;">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Rider</label>
                                <select name="rider_id" class="form-select form-select-sm" id="riderSel">
                                    <option value="">Assign later</option>
                                    @foreach($riders as $r)
                                        <option value="{{ $r->id }}" data-capacity="{{ $r->capacity }}">{{ $r->name }} · {{ \App\Models\Dispatch\DeliveryRider::VEHICLES[$r->vehicle_type] ?? $r->vehicle_type }}{{ in_array($r->id, $busyRiderIds) ? ' (on a run)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Route zone</label>
                                <select name="delivery_zone_id" class="form-select form-select-sm">
                                    <option value="">Most common in selection</option>
                                    @foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->code }} · {{ $z->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Leaves at</label>
                                <input type="datetime-local" name="planned_departure_at" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2 d-grid">
                                <button class="btn btn-success btn-sm" id="batchBtn" disabled><i class="ri-truck-line"></i> Create batch</button>
                            </div>
                        </div>
                        <div class="small mt-2 d-flex justify-content-between">
                            <span id="selSummary" class="text-muted">Tick the orders that go on one run.</span>
                            <span id="capWarn" class="text-danger fw-semibold"></span>
                        </div>
                    </div>
                @endif
            </form>
        </div>

        {{-- Active runs --}}
        <div class="col-xl-5">
            <x-cb.card title="Runs in progress" icon="ri-route-line" :count="$active->count()">
                @forelse($active as $b)
                    @php
                        $pct = $b->status === 'dispatched'
                            ? ($b->orders_count ? round($b->delivered_count / $b->orders_count * 100) : 0)
                            : ($b->parcels_count ? round($b->loaded_count / $b->parcels_count * 100) : 0);
                    @endphp
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="badge bg-dark fs-6">{{ $b->route_code }}</span>
                                <a href="{{ route('dispatch.show', $b) }}" class="fw-semibold font-monospace ms-1">{{ $b->code }}</a>
                                <div class="small text-muted mt-1">
                                    <i class="ri-user-line"></i> {{ $b->rider?->name ?? 'No rider yet' }}
                                    · {{ $b->orders_count }} stops · {{ $b->parcels_count }} parcels
                                </div>
                            </div>
                            <span class="badge bg-{{ $b->statusBadge() }}">{{ $b->statusLabel() }}</span>
                        </div>
                        <div class="progress-cell mt-2">
                            <div class="progress-track"><div class="progress-fill" style="width: {{ $pct }}%"></div></div>
                            <div class="progress-meta">
                                <span>{{ $b->status === 'dispatched' ? $b->delivered_count . ' of ' . $b->orders_count . ' delivered' : $b->loaded_count . ' of ' . $b->parcels_count . ' parcels loaded' }}</span>
                                <span>
                                    <a href="{{ route('dispatch.manifest', $b) }}" target="_blank">Manifest</a> ·
                                    <a href="{{ route('dispatch.show', $b) }}">Open</a>
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-3">No runs in progress.</div>
                @endforelse
            </x-cb.card>
        </div>
    </div>
@endif

</div>
</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const boxes = [...document.querySelectorAll('.js-order')];
    const btn = document.getElementById('batchBtn');
    if (!btn) return;
    const sum = document.getElementById('selSummary'), warn = document.getElementById('capWarn'), rider = document.getElementById('riderSel');
    function refresh() {
        const sel = boxes.filter(b => b.checked);
        const parcels = sel.reduce((n, b) => n + (+b.dataset.parcels || 0), 0);
        const zones = new Set(sel.map(b => b.dataset.zone));
        btn.disabled = sel.length === 0;
        btn.innerHTML = `<i class="ri-truck-line"></i> Create batch${sel.length ? ' (' + sel.length + ')' : ''}`;
        sum.textContent = sel.length
            ? `${sel.length} order(s) · ${parcels} parcel(s)` + (zones.size > 1 ? ` · ${zones.size} zones mixed` : '')
            : 'Tick the orders that go on one run.';
        const cap = +(rider.selectedOptions[0]?.dataset.capacity || 0);
        warn.textContent = cap && parcels > cap ? `Over this rider's capacity (${cap} parcels).` : '';
    }
    boxes.forEach(b => b.addEventListener('change', refresh));
    rider.addEventListener('change', refresh);
    document.querySelectorAll('.js-select-zone').forEach(z => z.addEventListener('click', () => {
        const group = boxes.filter(b => b.dataset.zone === z.dataset.zone && !b.disabled);
        const all = group.every(b => b.checked);
        group.forEach(b => b.checked = !all);
        refresh();
    }));
    refresh();
})();
</script>
@endpush
