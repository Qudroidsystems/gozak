@extends('layouts.master')

@section('title', 'Rider performance')

@section('content')
@php $t = $report['totals']; $pct = fn ($v) => $v === null ? '—' : $v . '%'; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('dispatch._nav', ['title' => 'Rider performance', 'subtitle' => 'Deliveries, punctuality, failure reasons and cash for ' . $from->format('j M') . ' – ' . $to->format('j M Y') . '.', 'back' => route('dispatch.index'), 'backLabel' => 'Dispatch board'])

    <form method="GET" class="d-flex flex-wrap gap-2 align-items-end mb-3">
        <div><label class="form-label small mb-1">From</label><input type="date" name="from" class="form-control form-control-sm" value="{{ $from->toDateString() }}"></div>
        <div><label class="form-label small mb-1">To</label><input type="date" name="to" class="form-control form-control-sm" value="{{ $to->toDateString() }}"></div>
        @if($branches->count() > 1)
            <div><label class="form-label small mb-1">Branch</label>
                <select name="branch" class="form-select form-select-sm"><option value="">All</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected($branchId == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        @endif
        <button class="btn btn-sm btn-primary">Show</button>
        <div class="btn-group btn-group-sm ms-auto">
            <a class="btn btn-outline-secondary" href="{{ route('dispatch.performance', ['from' => now()->toDateString(), 'to' => now()->toDateString()]) }}">Today</a>
            <a class="btn btn-outline-secondary" href="{{ route('dispatch.performance') }}">7 days</a>
            <a class="btn btn-outline-secondary" href="{{ route('dispatch.performance', ['from' => now()->subDays(29)->toDateString(), 'to' => now()->toDateString()]) }}">30 days</a>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-cb.stat label="Runs" :value="$t['runs']" icon="ri-route-line" accent="violet" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Delivered" :value="$t['delivered']" icon="ri-checkbox-circle-line" accent="green" :hint="$t['failed'] . ' failed · ' . $pct($t['success']) . ' success'" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="COD expected" :value="'₦' . number_format($t['cash_expected'])" icon="ri-money-dollar-circle-line" accent="sky" :hint="'₦' . number_format($t['cash_handed']) . ' handed in'" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Cash difference" :value="($t['cash_variance'] < 0 ? '−' : '') . '₦' . number_format(abs($t['cash_variance']), 2)" icon="ri-scales-3-line" :accent="$t['cash_variance'] < -0.009 ? 'rose' : 'green'" :hint="$t['cash_pending'] > 0 ? '₦' . number_format($t['cash_pending']) . ' not yet handed in' : 'all reconciled'" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-9">
            <x-cb.card title="By rider" icon="ri-e-bike-2-line" :count="count($report['rows'])" :flush="true">
                <div class="table-responsive">
                    <table class="cb-table">
                        <thead><tr>
                            <th>Rider</th><th class="num">Runs</th><th class="num">Delivered</th><th class="num">Failed</th><th class="num">Success</th>
                            <th class="num">On time</th><th class="num">Avg. time</th><th>Confirmed by</th><th class="num">Wrong codes</th>
                            <th class="num">Cash reported</th><th class="num">Handed in</th><th class="num">Difference</th>
                        </tr></thead>
                        <tbody>
                        @forelse($report['rows'] as $r)
                            <tr>
                                <td><div class="fw-semibold">{{ $r['rider']->name }}</div><div class="small text-muted">{{ $r['rider']->vehicleLabel() }}</div></td>
                                <td class="num">{{ $r['runs'] }}</td>
                                <td class="num fw-semibold">{{ $r['delivered'] }}</td>
                                <td class="num">{{ $r['failed'] }}</td>
                                <td class="num"><span class="{{ ($r['success'] ?? 100) < 85 ? 'text-danger fw-semibold' : '' }}">{{ $pct($r['success']) }}</span></td>
                                <td class="num"><span class="{{ ($r['on_time'] ?? 100) < 80 ? 'text-danger fw-semibold' : '' }}">{{ $pct($r['on_time']) }}</span></td>
                                <td class="num">{{ $r['avg_mins'] !== null ? $r['avg_mins'] . ' min' : '—' }}</td>
                                <td class="small">
                                    @if($r['methods']['code'])<span class="badge bg-success-subtle text-success">code {{ $r['methods']['code'] }}</span>@endif
                                    @if($r['methods']['photo'])<span class="badge bg-warning-subtle text-warning">photo {{ $r['methods']['photo'] }}</span>@endif
                                    @if($r['methods']['admin'])<span class="badge bg-secondary-subtle text-secondary">admin {{ $r['methods']['admin'] }}</span>@endif
                                    @if($r['methods']['none'] ?? 0)<span class="badge bg-light text-dark">rider {{ $r['methods']['none'] }}</span>@endif
                                </td>
                                <td class="num"><span class="{{ $r['code_fails'] >= 3 ? 'text-danger fw-semibold' : '' }}">{{ $r['code_fails'] }}</span></td>
                                <td class="num">₦{{ number_format($r['cash_reported'], 2) }}</td>
                                <td class="num">₦{{ number_format($r['cash_handed'], 2) }}@if($r['cash_pending'] > 0)<div class="small text-warning">₦{{ number_format($r['cash_pending']) }} pending</div>@endif</td>
                                <td class="num fw-semibold {{ $r['cash_variance'] < -0.009 ? 'text-danger' : '' }}">{{ $r['cash_variance'] < 0 ? '−' : '' }}₦{{ number_format(abs($r['cash_variance']), 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center text-muted py-4">No deliveries in this period.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-cb.card>
            <p class="small text-muted">
                <b>On time</b> compares the delivery time with the promised delivery time (a date without a time counts as {{ \App\Models\Dispatch\DispatchSetting::current()->default_promise_hour }}:00).
                <b>Avg. time</b> is from dispatch to the stop being confirmed. <b>Difference</b> is cash handed in minus cash the rider reported, for runs that have been reconciled.
            </p>
        </div>
        <div class="col-xl-3">
            <x-cb.card title="Why deliveries failed" icon="ri-close-circle-line">
                @php $max = max(1, $report['reasons']->max() ?? 1); @endphp
                @forelse($report['reasons'] as $reason => $n)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small"><span>{{ $reason }}</span><b>{{ $n }}</b></div>
                        <div class="progress-track"><div class="progress-fill" style="width: {{ round($n / $max * 100) }}%; background: #e11d48;"></div></div>
                    </div>
                @empty
                    <div class="text-muted small">No failed deliveries. 🎉</div>
                @endforelse
            </x-cb.card>
        </div>
    </div>

</div>
</div>
</div>
@endsection
