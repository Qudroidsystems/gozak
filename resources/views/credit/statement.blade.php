@extends('layouts.master')

@section('title', 'Statement ' . $st->number)

@section('content')
@php $n = fn ($v) => '₦' . number_format((float) $v, 2); $a = $st->account; @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => 'Statement ' . $st->number, 'subtitle' => ($a->user?->full_name ?? '') . ' · ' . $st->period_start->format('j M') . ' – ' . $st->period_end->format('j M Y')])

    <div class="row g-3">
        <div class="col-lg-4">
            <x-cb.card title="Summary" icon="ri-file-list-3-line">
                <div class="gzc-kv"><span>Customer</span><a href="{{ route('admin.credit.account', $a) }}">{{ $a->user?->full_name }}</a></div>
                <div class="gzc-kv"><span>Purchases</span><span>{{ $n($st->purchases) }}</span></div>
                <div class="gzc-kv"><span>Fees</span><span>{{ $n($st->fees) }}</span></div>
                <div class="gzc-kv"><span>Refunds & credits</span><span>−{{ $n($st->credits) }}</span></div>
                <div class="gzc-kv"><span>Paid early</span><span>−{{ $n($st->payments) }}</span></div>
                <div class="gzc-kv"><span>Amount due</span><b>{{ $n($st->amount_due) }}</b></div>
                <div class="gzc-kv"><span>Collected</span><b class="text-success">{{ $n($st->amount_paid) }}</b></div>
                <div class="gzc-kv"><span>Outstanding</span><b class="{{ $st->outstanding() > 0 ? 'text-danger' : '' }}">{{ $n($st->outstanding()) }}</b></div>
                <div class="gzc-kv"><span>Due date</span><span>{{ $st->due_date->format('D j M Y') }}</span></div>
                <div class="gzc-kv"><span>Status</span><span class="badge bg-{{ $st->statusBadge() }}">{{ str_replace('_', ' ', $st->status) }}</span></div>
                <div class="gzc-kv"><span>Reminders sent</span><span>{{ $st->reminders_sent ? collect(explode(',', $st->reminders_sent))->map(fn ($d) => $d === '0' ? 'due day' : $d . 'd before')->implode(', ') : '—' }}</span></div>
                @can('Manage credit')
                    @if($st->isOpen())
                        <form method="POST" action="{{ route('admin.credit.debit', $a) }}" class="mt-3">@csrf
                            <input type="hidden" name="statement_id" value="{{ $st->id }}">
                            <button class="btn btn-primary w-100"><i class="ri-flashlight-line"></i> Debit {{ $n($st->outstanding()) }} now</button>
                        </form>
                    @endif
                    @if($st->late_fee_applied_at)
                        <form method="POST" action="{{ route('admin.credit.waive', $st) }}" class="mt-2" onsubmit="return confirm('Waive the late fee?');">@csrf
                            <button class="btn btn-outline-secondary w-100">Waive late fee</button>
                        </form>
                    @endif
                @endcan
            </x-cb.card>
        </div>
        <div class="col-lg-8">
            <x-cb.card title="Items on this statement" icon="ri-list-check-2" :flush="true">
                <div class="table-responsive">
                    <table class="table gzc-table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Description</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                        @forelse($st->entries as $e)
                            <tr>
                                <td class="small">{{ $e->created_at->format('j M Y') }}</td>
                                <td>{{ $e->description }}@if($e->order_id) · <a href="{{ route('adminorders.show', $e->order_id) }}">order</a>@endif</td>
                                <td class="text-end gzc-money {{ $e->direction === 'credit' ? 'text-success' : '' }}">{{ $e->direction === 'credit' ? '−' : '' }}{{ $n($e->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">No items.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-cb.card>
            <x-cb.card title="Debit attempts" icon="ri-exchange-funds-line" :flush="true" class="mt-3">
                <div class="table-responsive">
                    <table class="table gzc-table table-sm mb-0">
                        <thead><tr><th>When</th><th>Method</th><th>Trigger</th><th class="text-end">Amount</th><th>Status</th><th>Reason</th></tr></thead>
                        <tbody>
                        @forelse($st->collectionAttempts as $at)
                            <tr>
                                <td class="small">{{ $at->created_at->format('j M H:i') }}</td>
                                <td>{{ $at->mandate?->label() ?? $at->channel }}</td>
                                <td class="small">{{ $at->trigger }}@if($at->initiator && $at->trigger === 'admin') · {{ $at->initiator->full_name }}@endif</td>
                                <td class="text-end gzc-money">{{ $n($at->amount) }}</td>
                                <td><span class="badge bg-{{ $at->statusBadge() }}">{{ $at->status }}</span></td>
                                <td class="small">{{ $at->failure_reason }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">No debits attempted yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-cb.card>
        </div>
    </div>

</div>
</div>
</div>
@endsection
