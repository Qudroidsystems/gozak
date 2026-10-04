@extends('layouts.master')

@section('title', 'Application · ' . $app->full_name)

@section('content')
@php $n = fn ($v) => '₦' . number_format((float) $v, 0); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => $app->full_name, 'subtitle' => 'Credit application #' . $app->id . ' · ' . $app->created_at->format('j M Y, H:i')])

    <div class="row g-3">
        <div class="col-lg-8">
            <x-cb.card title="Applicant" icon="ri-user-3-line">
                <div class="row">
                    <div class="col-md-6">
                        <div class="gzc-kv"><span>Full name</span><b>{{ $app->full_name }}</b></div>
                        <div class="gzc-kv"><span>Account name</span><span>{{ $user->full_name }}</span></div>
                        <div class="gzc-kv"><span>Email</span><span>{{ $user->email }}</span></div>
                        <div class="gzc-kv"><span>Phone</span><span>{{ $app->phone }}</span></div>
                        <div class="gzc-kv"><span>Date of birth</span><span>{{ $app->date_of_birth?->format('j M Y') }} ({{ $app->date_of_birth?->age }} yrs)</span></div>
                        <div class="gzc-kv"><span>BVN</span><span class="font-monospace">•••••••{{ $app->bvn_last4 }}</span></div>
                        <div class="gzc-kv"><span>Bank account</span><span>{{ $app->bank_name ?: '—' }} @if($app->account_last4)•••• {{ $app->account_last4 }}@endif</span></div>
                    </div>
                    <div class="col-md-6">
                        <div class="gzc-kv"><span>Employment</span><span>{{ $app->employmentLabel() }}</span></div>
                        <div class="gzc-kv"><span>Employer / business</span><span>{{ $app->employer ?: '—' }}</span></div>
                        <div class="gzc-kv"><span>Monthly income</span><span>{{ $app->incomeLabel() }}</span></div>
                        <div class="gzc-kv"><span>Address</span><span class="text-end">{{ $app->address }}, {{ $app->state }}</span></div>
                        <div class="gzc-kv"><span>Accepted terms</span><span>v{{ $app->terms_version }} · {{ $app->consented_at?->format('j M Y H:i') }}</span></div>
                        <div class="gzc-kv"><span>IP</span><span class="font-monospace small">{{ $app->consent_ip }}</span></div>
                    </div>
                </div>
                @if(strcasecmp(trim($app->full_name), trim($user->full_name)) !== 0)
                    <div class="alert alert-warning small mt-3 mb-0"><i class="ri-error-warning-line"></i> The name on the application differs from the name on the Gozak account. Check before approving.</div>
                @endif
            </x-cb.card>

            <x-cb.card title="Shopping history" icon="ri-shopping-bag-3-line" class="mt-3">
                <div class="row g-2 mb-3 text-center">
                    <div class="col"><div class="fs-4 fw-bold">{{ $stats['orders'] }}</div><div class="small text-muted">Orders</div></div>
                    <div class="col"><div class="fs-4 fw-bold text-success">{{ $stats['delivered'] }}</div><div class="small text-muted">Delivered</div></div>
                    <div class="col"><div class="fs-4 fw-bold text-danger">{{ $stats['cancelled'] }}</div><div class="small text-muted">Cancelled</div></div>
                    <div class="col"><div class="fs-4 fw-bold">{{ $n($stats['spent']) }}</div><div class="small text-muted">Spent</div></div>
                    <div class="col"><div class="fs-6 fw-bold">{{ $stats['since']?->format('M Y') }}</div><div class="small text-muted">Customer since</div></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm gzc-table mb-0">
                        <thead><tr><th>Order</th><th>Date</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                        @forelse($recentOrders as $o)
                            <tr><td><a href="{{ route('adminorders.show', $o->id) }}">{{ $o->invoice_number ?? strtoupper(substr($o->id, 0, 8)) }}</a></td><td>{{ $o->created_at->format('j M Y') }}</td><td>{{ $o->status }}</td><td class="text-end gzc-money">{{ $n($o->total_amount) }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No orders yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-cb.card>

            @if($history->isNotEmpty() || $account)
            <x-cb.card title="Earlier credit history" icon="ri-history-line" class="mt-3">
                @if($account)
                    <div class="gzc-kv"><span>Existing account</span><a href="{{ route('admin.credit.account', $account) }}">{{ $account->statusLabel() }} · limit {{ $n($account->credit_limit) }}</a></div>
                    <div class="gzc-kv"><span>Payments on time / late</span><b>{{ $account->on_time_payments }} / {{ $account->late_payments }}</b></div>
                @endif
                @foreach($history as $h)
                    <div class="gzc-kv"><span>{{ $h->created_at->format('j M Y') }}</span><span>{{ ucfirst($h->status) }}@if($h->decision_note) — {{ $h->decision_note }}@endif</span></div>
                @endforeach
            </x-cb.card>
            @endif
        </div>

        <div class="col-lg-4">
            @php [$bvnLabel, $bvnCls] = $app->bvnBadge(); @endphp
            <x-cb.card title="Identity (BVN)" icon="ri-fingerprint-line" class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-{{ $bvnCls }} fs-6">{{ $bvnLabel }}</span>
                    @if($app->bvn_checked_at)<span class="small text-muted">{{ $app->bvn_checked_at->diffForHumans() }}</span>@endif
                </div>
                <div class="gzc-kv"><span>Name on application</span><b>{{ $app->full_name }}</b></div>
                <div class="gzc-kv"><span>Name at the bank</span><b class="{{ $app->account_name && strcasecmp(preg_replace('/\s+/', ' ', trim($app->account_name)), preg_replace('/\s+/', ' ', trim($app->full_name))) !== 0 ? 'text-warning' : '' }}">{{ $app->account_name ?: '—' }}</b></div>
                <div class="gzc-kv"><span>Bank account</span><span>{{ $app->bank_name }} •••• {{ $app->account_last4 }}</span></div>
                @if($app->bvn_failure_reason)
                    <div class="alert alert-{{ $app->bvn_status === 'failed' ? 'danger' : 'warning' }} small mt-2 mb-0">{{ $app->bvn_failure_reason }}</div>
                @endif
                <p class="small text-muted mt-2 mb-2">Paystack asks the bank to confirm that this BVN, this bank account and the applicant's first and last name belong to the same person.</p>
                @can('Review credit applications')
                    @if($app->bvn_status !== 'verified')
                        <form method="POST" action="{{ route('admin.credit.bvn', $app) }}">@csrf
                            <button class="btn btn-sm btn-outline-primary w-100"><i class="ri-refresh-line"></i> {{ $app->bvn_status === 'pending' ? 'Check again' : 'Run BVN check' }}</button>
                        </form>
                    @endif
                @endcan
            </x-cb.card>

            <x-cb.card title="System assessment" icon="ri-shield-check-line">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="display-6 fw-bold text-{{ $app->risk_score >= 60 ? 'success' : ($app->risk_score >= 40 ? 'warning' : 'danger') }}">{{ $app->risk_score }}</div>
                    <div class="small text-muted">out of 100<br>{{ $app->risk_score >= 60 ? 'Lower risk' : ($app->risk_score >= 40 ? 'Medium risk' : 'Higher risk') }}</div>
                </div>
                <div class="gzc-bar {{ $app->risk_score < 40 ? 'warn' : '' }} mb-3"><div style="width: {{ $app->risk_score }}%"></div></div>
                @foreach($app->risk_factors ?? [] as $f)
                    <div class="gzc-kv"><span>{{ $f['label'] }}</span><b class="text-{{ $f['points'] >= 0 ? 'success' : 'danger' }}">{{ $f['points'] >= 0 ? '+' : '' }}{{ $f['points'] }}</b></div>
                @endforeach
                <div class="gzc-kv mt-2"><span>Requested</span><b>{{ $n($app->requested_limit) }}</b></div>
                <div class="gzc-kv"><span>Suggested limit</span><b>{{ $n($app->suggested_limit) }}</b></div>
                <p class="small text-muted mt-2 mb-0">The score is a guide from shopping history and stated income. BVN and identity are not verified automatically — check them before approving larger limits.</p>
            </x-cb.card>

            @if($app->status === 'pending')
                @can('Review credit applications')
                <x-cb.card title="Decision" icon="ri-scales-3-line" class="mt-3">
                    @if($app->bvn_status !== 'verified')
                        <div class="alert alert-warning small"><i class="ri-error-warning-line"></i> The BVN is <b>not verified</b>{{ $app->bvn_status === 'pending' ? ' yet (check in progress)' : '' }}. Approving now means lending without a confirmed identity.</div>
                    @endif
                    <form method="POST" action="{{ route('admin.credit.approve', $app) }}" onsubmit="return confirm('{{ $app->bvn_status === 'verified' ? 'Approve with this limit?' : 'The BVN is NOT verified. Approve anyway?' }}');">@csrf
                        <label class="form-label fw-semibold">Credit limit (₦)</label>
                        <input type="number" class="form-control mb-2" name="limit" step="1000" min="{{ $settings->min_limit }}" max="{{ $settings->max_limit }}" value="{{ (int) $app->suggested_limit }}" required>
                        <div class="small text-muted mb-2">Between {{ $n($settings->min_limit) }} and {{ $n($settings->max_limit) }}.</div>
                        <textarea class="form-control mb-2" name="note" rows="2" placeholder="Internal note (optional)"></textarea>
                        <button class="btn btn-success w-100"><i class="ri-check-line"></i> Approve</button>
                    </form>
                    <hr>
                    <form method="POST" action="{{ route('admin.credit.reject', $app) }}" onsubmit="return confirm('Reject this application?');">@csrf
                        <textarea class="form-control mb-2" name="reason" rows="2" placeholder="Reason (internal, required)" required></textarea>
                        <button class="btn btn-outline-danger w-100"><i class="ri-close-line"></i> Reject</button>
                    </form>
                </x-cb.card>
                @endcan
            @else
                <x-cb.card title="Decision" icon="ri-scales-3-line" class="mt-3">
                    <div class="gzc-kv"><span>Status</span><b>{{ ucfirst($app->status) }}</b></div>
                    @if($app->approved_limit)<div class="gzc-kv"><span>Approved limit</span><b>{{ $n($app->approved_limit) }}</b></div>@endif
                    <div class="gzc-kv"><span>By</span><span>{{ $app->reviewer?->full_name ?? '—' }}</span></div>
                    <div class="gzc-kv"><span>When</span><span>{{ $app->reviewed_at?->format('j M Y H:i') }}</span></div>
                    @if($app->decision_note)<div class="small mt-2">{{ $app->decision_note }}</div>@endif
                </x-cb.card>
            @endif
        </div>
    </div>

</div>
</div>
</div>
@endsection
