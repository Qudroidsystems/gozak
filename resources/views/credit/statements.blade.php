@extends('layouts.master')

@section('title', 'Credit statements')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => 'Statements', 'subtitle' => 'Monthly bills and what has been collected.'])

    <x-cb.card :flush="true">
        <x-slot:tools>
            <form class="d-flex gap-2 flex-wrap align-items-center" method="GET">
                <input type="hidden" name="status" value="{{ $status }}">
                <label class="small text-muted">Due</label>
                <input type="date" class="form-control form-control-sm" name="due_from" value="{{ request('due_from') }}">
                <span class="small">to</span>
                <input type="date" class="form-control form-control-sm" name="due_to" value="{{ request('due_to') }}">
                <button class="btn btn-sm btn-light">Filter</button>
            </form>
        </x-slot:tools>
        <div class="px-3 pt-3 d-flex justify-content-between align-items-end flex-wrap gap-2">
            <ul class="nav nav-tabs">
                @foreach(['open' => 'Open', 'overdue' => 'Overdue', 'paid' => 'Paid', 'waived' => 'Waived', 'all' => 'All'] as $key => $label)
                    <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.credit.statements', ['status' => $key] + request()->only('due_from', 'due_to')) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
            <div class="small pb-2">Total due <b class="gzc-money">₦{{ number_format($totals['due'], 2) }}</b> · collected <b class="gzc-money text-success">₦{{ number_format($totals['paid'], 2) }}</b></div>
        </div>
        <div class="table-responsive">
            <table class="table gzc-table table-hover mb-0">
                <thead><tr><th>Statement</th><th>Customer</th><th>Period</th><th>Due date</th><th class="text-end">Due</th><th class="text-end">Paid</th><th class="text-end">Outstanding</th><th>Status</th><th>Debits</th></tr></thead>
                <tbody>
                @forelse($rows as $st)
                    <tr>
                        <td><a href="{{ route('admin.credit.statement', $st) }}">{{ $st->number }}</a></td>
                        <td><a href="{{ route('admin.credit.account', $st->account) }}">{{ $st->account->user?->full_name }}</a></td>
                        <td class="small">{{ $st->period_start->format('j M') }} – {{ $st->period_end->format('j M Y') }}</td>
                        <td>{{ $st->due_date->format('j M Y') }}</td>
                        <td class="text-end gzc-money">₦{{ number_format($st->amount_due, 2) }}</td>
                        <td class="text-end gzc-money">₦{{ number_format($st->amount_paid, 2) }}</td>
                        <td class="text-end gzc-money fw-semibold">₦{{ number_format($st->outstanding(), 2) }}</td>
                        <td><span class="badge bg-{{ $st->statusBadge() }}">{{ str_replace('_', ' ', $st->status) }}</span>@if($st->late_fee_applied_at)<span class="badge bg-danger-subtle text-danger">late fee</span>@endif</td>
                        <td class="small">{{ $st->attempts }}@if($st->next_attempt_at && $st->isOpen())<div class="text-muted">next {{ $st->next_attempt_at->diffForHumans() }}</div>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">No statements here.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $rows->links() }}</div>
    </x-cb.card>

</div>
</div>
</div>
@endsection
