@extends('layouts.master')

@section('title', 'Credit accounts')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => 'Credit accounts', 'subtitle' => 'Every customer with Gozak Credit: limits, balances and status.'])

    <x-cb.card :flush="true">
        <x-slot:tools>
            <form class="d-flex gap-2 flex-wrap" method="GET">
                <input type="hidden" name="status" value="{{ $status }}">
                <input class="form-control form-control-sm" style="max-width:220px" name="q" value="{{ request('q') }}" placeholder="Name, email, phone, card last 4">
                <select class="form-select form-select-sm" name="sort" style="max-width:170px" onchange="this.form.submit()">
                    <option value="balance" @selected(request('sort', 'balance') === 'balance')>Highest balance</option>
                    <option value="limit" @selected(request('sort') === 'limit')>Highest limit</option>
                    <option value="newest" @selected(request('sort') === 'newest')>Newest</option>
                </select>
                <button class="btn btn-sm btn-light">Search</button>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.credit.accounts.export', request()->query()) }}"><i class="ri-download-2-line"></i> CSV</a>
            </form>
        </x-slot:tools>
        <div class="px-3 pt-3">
            <ul class="nav nav-tabs">
                @foreach(['all' => 'All', 'active' => 'Active', 'pending_mandate' => 'Awaiting bank link', 'overdue' => 'Overdue', 'suspended' => 'Suspended', 'frozen' => 'Frozen', 'closed' => 'Closed'] as $key => $label)
                    <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.credit.accounts', ['status' => $key] + request()->only('q', 'sort')) }}">{{ $label }}
                        @if(isset($counts[$key]))<span class="badge bg-light text-dark ms-1">{{ $counts[$key] }}</span>@endif</a></li>
                @endforeach
            </ul>
        </div>
        <div class="table-responsive">
            <table class="table gzc-table table-hover mb-0">
                <thead><tr><th>Customer</th><th>Card</th><th>Status</th><th class="text-end">Limit</th><th class="text-end">Balance</th><th style="width:160px">Used</th><th class="text-end">Available</th><th>History</th><th></th></tr></thead>
                <tbody>
                @forelse($rows as $a)
                    @php $pct = $a->credit_limit > 0 ? min(100, round($a->balance / $a->credit_limit * 100)) : 0; @endphp
                    <tr>
                        <td><div class="fw-semibold">{{ $a->user?->full_name }}</div><div class="small text-muted">{{ $a->user?->email }}</div></td>
                        <td class="font-monospace small">•••• {{ substr($a->card_number, -4) }}</td>
                        <td><span class="badge bg-{{ $a->statusBadge() }}">{{ $a->statusLabel() }}</span>
                            @if($a->overdue_count)<span class="badge bg-danger-subtle text-danger">{{ $a->overdue_count }} overdue</span>@endif</td>
                        <td class="text-end gzc-money">₦{{ number_format($a->credit_limit) }}</td>
                        <td class="text-end gzc-money fw-semibold">₦{{ number_format($a->balance, 2) }}</td>
                        <td><div class="gzc-bar {{ $pct >= 90 ? 'warn' : '' }}"><div style="width: {{ $pct }}%"></div></div><div class="small text-muted">{{ $pct }}%</div></td>
                        <td class="text-end gzc-money">₦{{ number_format($a->available(), 2) }}</td>
                        <td class="small"><span class="text-success">{{ $a->on_time_payments }} on time</span> · <span class="{{ $a->late_payments ? 'text-danger' : 'text-muted' }}">{{ $a->late_payments }} late</span></td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('admin.credit.account', $a) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">No accounts here.</td></tr>
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
