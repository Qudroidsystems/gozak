@extends('layouts.master')

@section('title', 'Gozak Credit')

@section('content')
@php $n = fn ($v) => '₦' . number_format((float) $v, 0); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => 'Gozak Credit'])

    @unless($settings->enabled)
        <div class="alert alert-warning d-flex gap-2 align-items-start">
            <i class="ri-information-line fs-5"></i>
            <div>Gozak Credit is <b>switched off</b>: customers can't see it in the app. Staff accounts can still test the full flow.
                Turn it on for pilot users or everyone in <a href="{{ route('admin.credit.settings') }}">Settings</a> once your FCCPC registration is complete.</div>
        </div>
    @endunless

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Outstanding (all customers)" :value="$n($outstanding)" icon="ri-wallet-3-line" accent="sky" :hint="$utilisation . '% of ' . $n($totalLimit) . ' limits used'" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Due on open statements" :value="$n($dueOpen)" icon="ri-calendar-check-line" accent="teal" :hint="'Next debit ' . $nextDue->format('j M')" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Overdue" :value="$n($overdueAmt)" icon="ri-alarm-warning-line" :accent="$overdueAmt > 0 ? 'rose' : 'green'" :hint="$overdueCount . ' customer(s)'" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Collection rate (60 days)" :value="$collectionRate === null ? '—' : $collectionRate . '%'" icon="ri-percent-line" :accent="($collectionRate ?? 100) >= 90 ? 'green' : 'amber'" :hint="$onTimeRate === null ? 'No statements due yet' : $onTimeRate . '% paid on time'" /></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Spent on credit this month" :value="$n($spentMonth)" icon="ri-shopping-bag-3-line" accent="amber" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Collected this month" :value="$n($collectedMonth)" icon="ri-hand-coin-line" accent="green" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Fee income this month" :value="$n($feesMonth)" icon="ri-money-dollar-circle-line" accent="teal" hint="Credit fees + late fees" /></div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('admin.credit.applications') }}" class="text-reset">
                <x-cb.stat label="Applications waiting" :value="$pendingApps" icon="ri-file-user-line" :accent="$pendingApps ? 'rose' : 'sky'" hint="Tap to review" />
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Last 30 days — spending vs collections" icon="ri-line-chart-line">
                <canvas id="gzcChart" height="110" aria-label="Daily credit spending and collections"></canvas>
            </x-cb.card>

            <x-cb.card title="Debits in the next 7 days" icon="ri-calendar-schedule-line" :count="$upcoming->count()" :flush="true" class="mt-3">
                <div class="table-responsive">
                    <table class="table gzc-table mb-0">
                        <thead><tr><th>Customer</th><th>Statement</th><th>Due</th><th class="text-end">Amount</th><th>Method</th></tr></thead>
                        <tbody>
                        @forelse($upcoming as $st)
                            @php $prim = $st->account->activeMandates()->where('is_primary', true)->first(); @endphp
                            <tr>
                                <td><a href="{{ route('admin.credit.account', $st->account) }}">{{ $st->account->user?->full_name }}</a></td>
                                <td><a href="{{ route('admin.credit.statement', $st) }}">{{ $st->number }}</a></td>
                                <td>{{ $st->due_date->format('D j M') }}</td>
                                <td class="text-end gzc-money">{{ $n($st->outstanding()) }}</td>
                                <td>@if($prim)<span class="badge bg-success-subtle text-success">{{ $prim->label() }}</span>@else<span class="badge bg-danger-subtle text-danger">No mandate</span>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No debits scheduled this week.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-cb.card>

            <x-cb.card title="Overdue" icon="ri-alarm-warning-line" :count="$overdueList->count()" :flush="true" class="mt-3">
                <div class="table-responsive">
                    <table class="table gzc-table mb-0">
                        <thead><tr><th>Customer</th><th>Statement</th><th>Due</th><th>Days late</th><th class="text-end">Outstanding</th><th></th></tr></thead>
                        <tbody>
                        @forelse($overdueList as $st)
                            <tr>
                                <td><a href="{{ route('admin.credit.account', $st->account) }}">{{ $st->account->user?->full_name }}</a></td>
                                <td><a href="{{ route('admin.credit.statement', $st) }}">{{ $st->number }}</a></td>
                                <td>{{ $st->due_date->format('j M') }}</td>
                                <td><span class="badge bg-danger">{{ (int) $st->due_date->diffInDays(now()) }}</span></td>
                                <td class="text-end gzc-money">{{ $n($st->outstanding()) }}</td>
                                <td class="text-end">
                                    @can('Manage credit')
                                    <form method="POST" action="{{ route('admin.credit.debit', $st->account) }}" class="d-inline">@csrf
                                        <input type="hidden" name="statement_id" value="{{ $st->id }}">
                                        <button class="btn btn-sm btn-outline-primary">Debit now</button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Nothing overdue. 🎉</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-cb.card>
        </div>

        <div class="col-xl-4">
            <x-cb.card title="Accounts" icon="ri-bank-card-line">
                @php
                    $labels = ['active' => ['Active', 'success'], 'pending_mandate' => ['Awaiting bank link', 'info'], 'suspended' => ['Suspended (overdue)', 'danger'], 'frozen' => ['Frozen', 'warning'], 'closed' => ['Closed', 'secondary']];
                @endphp
                @foreach($labels as $key => [$label, $cls])
                    <a class="gzc-kv text-reset" href="{{ route('admin.credit.accounts', ['status' => $key]) }}">
                        <span><span class="badge bg-{{ $cls }} me-1">&nbsp;</span>{{ $label }}</span>
                        <b>{{ (int) ($accounts->get($key)?->n ?? 0) }}</b>
                    </a>
                @endforeach
            </x-cb.card>

            <x-cb.card title="Applications to review" icon="ri-file-user-line" :count="$pendingApps" class="mt-3">
                @forelse($applications as $app)
                    <a href="{{ route('admin.credit.application', $app) }}" class="d-flex justify-content-between align-items-center py-2 border-bottom text-reset">
                        <div>
                            <div class="fw-semibold">{{ $app->full_name }}</div>
                            <div class="small text-muted">{{ $app->created_at->diffForHumans() }} · asks {{ $n($app->requested_limit) }}</div>
                        </div>
                        <span class="badge bg-{{ $app->risk_score >= 60 ? 'success' : ($app->risk_score >= 40 ? 'warning' : 'danger') }}">{{ $app->risk_score }}</span>
                    </a>
                @empty
                    <div class="text-muted small">No applications waiting.</div>
                @endforelse
            </x-cb.card>

            <x-cb.card title="Recent failed debits" icon="ri-error-warning-line" class="mt-3">
                @forelse($failed as $at)
                    <div class="py-2 border-bottom small">
                        <div class="d-flex justify-content-between"><a href="{{ route('admin.credit.account', $at->account) }}" class="fw-semibold">{{ $at->account->user?->full_name }}</a><span class="gzc-money">{{ $n($at->amount) }}</span></div>
                        <div class="text-muted">{{ $at->mandate?->label() ?? $at->channel }} · {{ $at->created_at->diffForHumans() }}</div>
                        <div class="text-danger">{{ \Illuminate\Support\Str::limit($at->failure_reason, 80) }}</div>
                    </div>
                @empty
                    <div class="text-muted small">No failed debits.</div>
                @endforelse
            </x-cb.card>

            <x-cb.card title="Billing calendar" icon="ri-calendar-2-line" class="mt-3">
                <div class="gzc-kv"><span>Next statement</span><b>{{ $nextStatement->format('D j M Y') }}</b></div>
                <div class="gzc-kv"><span>Next automatic debit</span><b>{{ $nextDue->format('D j M Y') }}</b></div>
                <div class="gzc-kv"><span>Late fee after</span><b>{{ $settings->grace_days }} day(s) grace</b></div>
                <div class="gzc-kv"><span>Retries</span><b>{{ $settings->max_attempts }}× every {{ $settings->retry_hours }}h</b></div>
                @can('Manage credit')
                <form method="POST" action="{{ route('admin.credit.run') }}" class="mt-3" onsubmit="return confirm('Run the billing cycle now? Statements due will be issued and due debits attempted.');">@csrf
                    <button class="btn btn-sm btn-outline-secondary w-100"><i class="ri-play-circle-line"></i> Run billing now</button>
                </form>
                @endcan
            </x-cb.card>
        </div>
    </div>

</div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    var el = document.getElementById('gzcChart');
    if (!el || !window.Chart) return;
    var d = @json($chart);
    new Chart(el, {
        type: 'bar',
        data: { labels: d.labels, datasets: [
            { label: 'Spent on credit', data: d.spent, backgroundColor: 'rgba(255,87,34,.75)', borderRadius: 4 },
            { label: 'Collected', data: d.collected, backgroundColor: 'rgba(13,148,136,.75)', borderRadius: 4 }
        ] },
        options: { responsive: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ₦' + Number(c.raw).toLocaleString(); } } } },
                   scales: { y: { ticks: { callback: function (v) { return '₦' + Number(v).toLocaleString(); } } } } }
    });
})();
</script>
@endpush
