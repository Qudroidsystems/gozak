{{-- Shared header for every Gozak Credit admin page. --}}
@php
    $gzcS = \App\Models\Credit\CreditSetting::current();
    $gzcPending = \App\Models\Credit\CreditApplication::where('status', 'pending')->count();
    $gzcOverdue = \App\Models\Credit\CreditStatement::where('status', 'overdue')->count();
    $gzcFailed = \App\Models\Credit\CreditCollectionAttempt::where('status', 'failed')->where('created_at', '>=', now()->subDays(2))->count();
    $tabs = [
        ['admin.credit.dashboard', 'Overview', 'ri-dashboard-3-line', null],
        ['admin.credit.applications', 'Applications', 'ri-file-user-line', $gzcPending ?: null],
        ['admin.credit.accounts', 'Accounts', 'ri-bank-card-line', null],
        ['admin.credit.statements', 'Statements', 'ri-file-list-3-line', $gzcOverdue ? $gzcOverdue . ' overdue' : null],
        ['admin.credit.collections', 'Collections', 'ri-exchange-funds-line', $gzcFailed ? $gzcFailed . ' failed' : null],
        ['admin.credit.audit', 'Activity log', 'ri-history-line', null],
    ];
@endphp

<x-cb.hero :title="$title ?? 'Gozak Credit'" icon="ri-bank-card-2-line" :subtitle="$subtitle ?? 'Buy now, pay at the end of the month — automatic bank debits with card backup.'">
    <x-slot:pills>
        @if($gzcS->enabled)
            <span class="cb-meta-pill"><i class="ri-checkbox-circle-line"></i> Live · {{ $gzcS->audience === 'everyone' ? 'all customers' : 'pilot users only' }}</span>
        @else
            <span class="cb-meta-pill"><i class="ri-pause-circle-line"></i> Switched off for customers</span>
        @endif
        <span class="cb-meta-pill"><i class="ri-percent-line"></i> {{ rtrim(rtrim(number_format($gzcS->markup_percent, 2), '0'), '.') }}% credit fee</span>
        <span class="cb-meta-pill"><i class="ri-calendar-line"></i> Statement {{ $gzcS->statement_day }}th · debit {{ $gzcS->due_day }}th</span>
    </x-slot:pills>
    <x-slot:actions>
        @can('Manage credit settings')
            <a class="cb-hero-btn" href="{{ route('admin.credit.settings') }}"><i class="ri-settings-3-line"></i> Settings</a>
        @endcan
        {{ $actions ?? '' }}
    </x-slot:actions>
</x-cb.hero>

<ul class="nav nav-pills gap-1 mb-3 flex-wrap">
    @foreach($tabs as [$route, $label, $icon, $badge])
        <li class="nav-item">
            <a class="nav-link {{ Route::is($route) || (Route::is($route . '*') && $route !== 'admin.credit.dashboard') ? 'active' : '' }}" href="{{ route($route) }}">
                <i class="{{ $icon }} me-1"></i>{{ $label }}
                @if($badge)<span class="badge bg-danger ms-1">{{ $badge }}</span>@endif
            </a>
        </li>
    @endforeach
</ul>

@foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $cls)
    @if(session($key))
        <div class="alert alert-{{ $cls }} alert-dismissible fade show">{{ session($key) }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
@endforeach
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@once
@push('styles')
<style>
    .gzc-money { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .gzc-table td, .gzc-table th { vertical-align: middle; }
    .gzc-table th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--cb-muted, #64748b); }
    .gzc-card-visual { border-radius: 18px; padding: 20px; color: #fff; background: linear-gradient(135deg, #ff5722 0%, #ff8a50 55%, #ffb27d 100%); box-shadow: 0 12px 30px rgba(255, 87, 34, .25); position: relative; overflow: hidden; min-height: 190px; }
    .gzc-card-visual::after { content: ''; position: absolute; right: -40px; top: -40px; width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,.12); }
    .gzc-card-visual .num { font-family: ui-monospace, monospace; font-size: 18px; letter-spacing: 2px; }
    .gzc-card-visual.dim { filter: grayscale(.7); }
    .gzc-kv { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; border-bottom: 1px dashed var(--cb-border, #e2e8f0); font-size: 13.5px; }
    .gzc-kv:last-child { border-bottom: 0; }
    .gzc-kv span:first-child { color: var(--cb-muted, #64748b); }
    .gzc-bar { height: 8px; border-radius: 6px; background: var(--cb-surface-2, #eef2f5); overflow: hidden; }
    .gzc-bar > div { height: 100%; background: linear-gradient(90deg, #0d9488, #22c55e); }
    .gzc-bar.warn > div { background: linear-gradient(90deg, #f59e0b, #ef4444); }
    .gzc-timeline { list-style: none; padding: 0; margin: 0; }
    .gzc-timeline li { padding: 8px 0 8px 18px; border-left: 2px solid var(--cb-border, #e2e8f0); position: relative; font-size: 13px; }
    .gzc-timeline li::before { content: ''; position: absolute; left: -6px; top: 13px; width: 10px; height: 10px; border-radius: 50%; background: var(--cb-teal, #0d9488); }
</style>
@endpush
@endonce
