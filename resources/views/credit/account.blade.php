@extends('layouts.master')

@section('title', 'Credit · ' . ($a->user?->full_name ?? 'Account'))

@section('content')
@php
    $n = fn ($v) => '₦' . number_format((float) $v, 2);
    $pct = $a->credit_limit > 0 ? min(100, round($a->balance / $a->credit_limit * 100)) : 0;
    $due = $openStatements->sum(fn ($s) => $s->outstanding());
@endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => $a->user?->full_name ?? 'Credit account', 'subtitle' => $a->user?->email . ' · account #' . $a->id . ' · opened ' . $a->created_at->format('j M Y')])

    <div class="row g-3">
        {{-- Left: card + numbers + actions --}}
        <div class="col-xl-4">
            <div class="gzc-card-visual {{ $a->canSpend() ? '' : 'dim' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="fw-bold">GOZAK CREDIT</div>
                    <span class="badge bg-light text-dark">{{ $a->statusLabel() }}</span>
                </div>
                <div class="num mt-4">{{ trim(chunk_split($a->card_number, 4, ' ')) }}</div>
                <div class="d-flex justify-content-between mt-3 small">
                    <div><div class="opacity-75">CARD HOLDER</div><div class="fw-semibold">{{ $a->card_name }}</div></div>
                    <div class="text-end"><div class="opacity-75">VALID THRU</div><div class="fw-semibold">{{ $a->card_expires_on?->format('m/y') }}</div></div>
                </div>
            </div>

            <x-cb.card class="mt-3">
                <div class="gzc-kv"><span>Credit limit</span><b>{{ $n($a->credit_limit) }}</b></div>
                <div class="gzc-kv"><span>Balance (owed)</span><b>{{ $n($a->balance) }}</b></div>
                <div class="gzc-kv"><span>Available</span><b class="text-success">{{ $n($a->available()) }}</b></div>
                <div class="my-2"><div class="gzc-bar {{ $pct >= 90 ? 'warn' : '' }}"><div style="width: {{ $pct }}%"></div></div><div class="small text-muted mt-1">{{ $pct }}% used</div></div>
                <div class="gzc-kv"><span>Due on statements</span><b class="{{ $due > 0 ? 'text-danger' : '' }}">{{ $n($due) }}</b></div>
                <div class="gzc-kv"><span>Spent since last statement</span><span>{{ $n(max(0, $a->unbilled)) }}</span></div>
                <div class="gzc-kv"><span>Credit fee</span><span>{{ $a->markup() }}%{{ $a->markup_percent === null ? ' (standard)' : ' (custom)' }}</span></div>
                <div class="gzc-kv"><span>Payments on time / late</span><span><b class="text-success">{{ $a->on_time_payments }}</b> / <b class="text-danger">{{ $a->late_payments }}</b></span></div>
                @if($a->status_reason)<div class="alert alert-warning small mt-2 mb-0">{{ $a->status_reason }}</div>@endif
            </x-cb.card>

            @can('Manage credit')
            <x-cb.card title="Actions" icon="ri-tools-line" class="mt-3">
                <form method="POST" action="{{ route('admin.credit.debit', $a) }}" class="mb-2" onsubmit="return confirm('Debit all open statements now?');">@csrf
                    <div class="input-group input-group-sm">
                        <select class="form-select" name="channel"><option value="">Default method</option><option value="direct_debit">Bank mandate</option><option value="card">Card</option></select>
                        <button class="btn btn-primary" {{ $due > 0 ? '' : 'disabled' }}><i class="ri-flashlight-line"></i> Debit {{ $n($due) }} now</button>
                    </div>
                </form>

                <details class="mb-2"><summary class="fw-semibold small">Change credit limit</summary>
                    <form method="POST" action="{{ route('admin.credit.limit', $a) }}" class="mt-2">@csrf
                        <input type="number" class="form-control form-control-sm mb-1" name="limit" step="1000" min="0" max="{{ $settings->max_limit }}" value="{{ (int) $a->credit_limit }}">
                        <input class="form-control form-control-sm mb-1" name="reason" placeholder="Reason">
                        <button class="btn btn-sm btn-outline-primary w-100">Save limit</button>
                    </form>
                </details>

                <details class="mb-2"><summary class="fw-semibold small">Record a payment received outside the app</summary>
                    <form method="POST" action="{{ route('admin.credit.payment', $a) }}" class="mt-2" onsubmit="return confirm('Record this payment?');">@csrf
                        <input type="number" class="form-control form-control-sm mb-1" name="amount" step="0.01" min="1" max="{{ $a->balance }}" placeholder="Amount (₦)" required>
                        <input class="form-control form-control-sm mb-1" name="reference" placeholder="Bank transfer reference" required>
                        <input class="form-control form-control-sm mb-1" name="note" placeholder="Note (optional)">
                        <button class="btn btn-sm btn-outline-success w-100">Record payment</button>
                    </form>
                </details>

                <details class="mb-2"><summary class="fw-semibold small">Adjustment (charge or credit)</summary>
                    <form method="POST" action="{{ route('admin.credit.adjust', $a) }}" class="mt-2" onsubmit="return confirm('Post this adjustment?');">@csrf
                        <select class="form-select form-select-sm mb-1" name="direction"><option value="credit">Credit the customer (reduce balance)</option><option value="debit">Charge the customer (increase balance)</option></select>
                        <input type="number" class="form-control form-control-sm mb-1" name="amount" step="0.01" min="0.01" placeholder="Amount (₦)" required>
                        <input class="form-control form-control-sm mb-1" name="reason" placeholder="Reason (shown to the customer)" required>
                        <button class="btn btn-sm btn-outline-secondary w-100">Post adjustment</button>
                    </form>
                </details>

                <details class="mb-2"><summary class="fw-semibold small">Custom credit fee</summary>
                    <form method="POST" action="{{ route('admin.credit.markup', $a) }}" class="mt-2">@csrf
                        <div class="input-group input-group-sm mb-1"><input type="number" class="form-control" name="markup_percent" step="0.25" min="0" max="50" value="{{ $a->markup_percent }}" placeholder="Standard ({{ $settings->markup_percent }}%)"><span class="input-group-text">%</span></div>
                        <button class="btn btn-sm btn-outline-secondary w-100">Save (blank = standard)</button>
                    </form>
                </details>

                <details class="mb-2"><summary class="fw-semibold small">Freeze, unfreeze or close</summary>
                    <form method="POST" action="{{ route('admin.credit.status', $a) }}" class="mt-2" onsubmit="return confirm('Change account status?');">@csrf
                        <select class="form-select form-select-sm mb-1" name="status">
                            <option value="frozen">Freeze (stop new purchases)</option>
                            <option value="active">Unfreeze / reactivate</option>
                            <option value="closed">Close account (balance must be ₦0)</option>
                        </select>
                        <input class="form-control form-control-sm mb-1" name="reason" placeholder="Reason">
                        <button class="btn btn-sm btn-outline-danger w-100">Update status</button>
                    </form>
                </details>

                <form method="POST" action="{{ route('admin.credit.recalculate', $a) }}">@csrf
                    <button class="btn btn-sm btn-link px-0"><i class="ri-calculator-line"></i> Re-check balance against the ledger</button>
                </form>
            </x-cb.card>
            @endcan

            <x-cb.card title="Repayment methods" icon="ri-bank-line" class="mt-3">
                @forelse($mandates as $m)
                    <div class="d-flex justify-content-between align-items-start py-2 border-bottom">
                        <div>
                            <div class="fw-semibold"><i class="{{ $m->type === 'card' ? 'ri-bank-card-line' : 'ri-bank-line' }}"></i> {{ $m->label() }}
                                @if($m->is_primary)<span class="badge bg-primary">Default</span>@endif</div>
                            <div class="small text-muted">{{ $m->type === 'card' ? 'Card backup' : 'Bank direct debit' }} · {{ ucfirst($m->status) }}
                                @if($m->account_name) · {{ $m->account_name }}@endif
                                @if($m->expiry) · exp {{ $m->expiry }}@endif
                                @if($m->failures) · <span class="text-danger">{{ $m->failures }} failed</span>@endif</div>
                        </div>
                        @can('Manage credit')
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></button>
                            <div class="dropdown-menu dropdown-menu-end">
                                @if($m->status === 'pending')
                                    <form method="POST" action="{{ route('admin.credit.mandate.refresh', $m) }}">@csrf<button class="dropdown-item">Check status with Paystack</button></form>
                                @endif
                                @if($m->status === 'active' && !$m->is_primary)
                                    <form method="POST" action="{{ route('admin.credit.mandate.primary', $m) }}">@csrf<button class="dropdown-item">Make default</button></form>
                                @endif
                                @if(in_array($m->status, ['active', 'pending']))
                                    <form method="POST" action="{{ route('admin.credit.mandate.revoke', $m) }}" onsubmit="return confirm('Remove this payment method? Paystack will stop it being charged.');">@csrf<button class="dropdown-item text-danger">Remove</button></form>
                                @endif
                            </div>
                        </div>
                        @endcan
                    </div>
                @empty
                    <div class="text-muted small">The customer hasn't linked a bank account or card yet.</div>
                @endforelse
            </x-cb.card>
        </div>

        {{-- Right: tabs --}}
        <div class="col-xl-8">
            <x-cb.card :flush="true">
                <div class="px-3 pt-3">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#t-st">Statements</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t-ledger">Transactions</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t-col">Debit attempts</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t-audit">Activity</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t-app">Application</a></li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="t-st">
                        <div class="table-responsive">
                            <table class="table gzc-table mb-0">
                                <thead><tr><th>Statement</th><th>Period</th><th>Due</th><th class="text-end">Amount due</th><th class="text-end">Paid</th><th>Status</th><th>Debits</th><th></th></tr></thead>
                                <tbody>
                                @forelse($statements as $st)
                                    <tr>
                                        <td><a href="{{ route('admin.credit.statement', $st) }}">{{ $st->number }}</a></td>
                                        <td class="small">{{ $st->period_start->format('j M') }} – {{ $st->period_end->format('j M Y') }}</td>
                                        <td>{{ $st->due_date->format('j M Y') }}</td>
                                        <td class="text-end gzc-money">{{ $n($st->amount_due) }}</td>
                                        <td class="text-end gzc-money">{{ $n($st->amount_paid) }}</td>
                                        <td><span class="badge bg-{{ $st->statusBadge() }}">{{ str_replace('_', ' ', $st->status) }}</span></td>
                                        <td class="small">{{ $st->attempts }}@if($st->next_attempt_at && $st->isOpen())<div class="text-muted">next {{ $st->next_attempt_at->diffForHumans() }}</div>@endif</td>
                                        <td class="text-end">
                                            @can('Manage credit')
                                            @if($st->isOpen())
                                                <form method="POST" action="{{ route('admin.credit.debit', $a) }}" class="d-inline">@csrf<input type="hidden" name="statement_id" value="{{ $st->id }}"><button class="btn btn-sm btn-outline-primary">Debit</button></form>
                                            @endif
                                            @if($st->late_fee_applied_at)
                                                <form method="POST" action="{{ route('admin.credit.waive', $st) }}" class="d-inline" onsubmit="return confirm('Waive the late fee?');">@csrf<button class="btn btn-sm btn-outline-secondary">Waive fee</button></form>
                                            @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">No statements yet. The first one is issued after {{ $settings->statementDateFor(now())->format('j M') }}.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="t-ledger">
                        <div class="table-responsive">
                            <table class="table gzc-table table-sm mb-0">
                                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th class="text-end">Amount</th><th class="text-end">Balance</th><th>By</th></tr></thead>
                                <tbody>
                                @forelse($ledger as $e)
                                    <tr>
                                        <td class="small">{{ $e->created_at->format('j M Y H:i') }}</td>
                                        <td><span class="badge bg-{{ $e->direction === 'debit' ? 'warning-subtle text-warning' : 'success-subtle text-success' }}">{{ \App\Models\Credit\CreditLedgerEntry::LABELS[$e->type] ?? $e->type }}</span></td>
                                        <td>{{ $e->description }}@if($e->order_id) · <a href="{{ route('adminorders.show', $e->order_id) }}">order</a>@endif</td>
                                        <td class="text-end gzc-money {{ $e->direction === 'credit' ? 'text-success' : '' }}">{{ $e->direction === 'credit' ? '−' : '+' }}{{ $n($e->amount) }}</td>
                                        <td class="text-end gzc-money">{{ $n($e->balance_after) }}</td>
                                        <td class="small text-muted">{{ $e->creator?->full_name ?? 'System' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No transactions yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="t-col">
                        <div class="table-responsive">
                            <table class="table gzc-table table-sm mb-0">
                                <thead><tr><th>When</th><th>Method</th><th>Trigger</th><th class="text-end">Amount</th><th>Status</th><th>Reason / reference</th></tr></thead>
                                <tbody>
                                @forelse($attempts as $at)
                                    <tr>
                                        <td class="small">{{ $at->created_at->format('j M H:i') }}</td>
                                        <td>{{ $at->mandate?->label() ?? ucfirst(str_replace('_', ' ', $at->channel)) }}</td>
                                        <td class="small">{{ $at->trigger }}@if($at->initiator && $at->trigger === 'admin') · {{ $at->initiator->full_name }}@endif</td>
                                        <td class="text-end gzc-money">{{ $n($at->amount) }}</td>
                                        <td><span class="badge bg-{{ $at->statusBadge() }}">{{ $at->status }}</span></td>
                                        <td class="small">{{ $at->failure_reason }}<div class="text-muted font-monospace">{{ $at->reference }}</div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No debit attempts yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade p-3" id="t-audit">
                        <ul class="gzc-timeline">
                            @forelse($audit as $log)
                                <li><b>{{ $log->summary }}</b><div class="text-muted small">{{ $log->created_at->format('j M Y H:i') }} · {{ $log->actor?->full_name ?? 'System' }} · {{ $log->action }}</div></li>
                            @empty
                                <li class="text-muted">No activity yet.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="tab-pane fade p-3" id="t-app">
                        @if($a->application)
                            <div class="gzc-kv"><span>Applied</span><span>{{ $a->application->created_at->format('j M Y') }}</span></div>
                            <div class="gzc-kv"><span>Employment</span><span>{{ $a->application->employmentLabel() }} {{ $a->application->employer ? '· ' . $a->application->employer : '' }}</span></div>
                            <div class="gzc-kv"><span>Income</span><span>{{ $a->application->incomeLabel() }}</span></div>
                            <div class="gzc-kv"><span>Phone</span><span>{{ $a->application->phone }}</span></div>
                            <div class="gzc-kv"><span>Address</span><span>{{ $a->application->address }}, {{ $a->application->state }}</span></div>
                            <div class="gzc-kv"><span>Score at application</span><span>{{ $a->application->risk_score }}/100</span></div>
                            <a class="btn btn-sm btn-light mt-2" href="{{ route('admin.credit.application', $a->application) }}">Open application</a>
                        @else
                            <div class="text-muted">No application on file.</div>
                        @endif
                    </div>
                </div>
            </x-cb.card>
        </div>
    </div>

</div>
</div>
</div>
@endsection
