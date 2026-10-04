@extends('layouts.master')

@section('title', 'Credit collections')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => 'Collections', 'subtitle' => 'Every automatic, admin and in-app debit, with its result.'])

    @php
        $by = fn ($ch) => $stats->where('channel', $ch);
        $rate = function ($ch) use ($by) {
            $all = $by($ch)->sum('n'); $ok = $by($ch)->where('status', 'success')->sum('n');
            return $all ? round($ok / $all * 100) . '%' : '—';
        };
    @endphp
    <div class="row g-3 mb-3">
        <div class="col-md-3"><x-cb.stat label="Bank debit success (30 days)" :value="$rate('direct_debit')" icon="ri-bank-line" accent="teal" :hint="$by('direct_debit')->sum('n') . ' attempts'" /></div>
        <div class="col-md-3"><x-cb.stat label="Card debit success (30 days)" :value="$rate('card')" icon="ri-bank-card-line" accent="sky" :hint="$by('card')->sum('n') . ' attempts'" /></div>
        <div class="col-md-3"><x-cb.stat label="Collected by auto-debit (30 days)" :value="'₦' . number_format($stats->where('status', 'success')->sum('amt'))" icon="ri-hand-coin-line" accent="green" /></div>
        <div class="col-md-3"><x-cb.stat label="Failed debits (30 days)" :value="$stats->where('status', 'failed')->sum('n')" icon="ri-error-warning-line" accent="rose" /></div>
    </div>

    <x-cb.card :flush="true">
        <x-slot:tools>
            <form class="d-flex gap-2" method="GET">
                <input type="hidden" name="status" value="{{ $status }}">
                <select class="form-select form-select-sm" name="channel" onchange="this.form.submit()">
                    <option value="">All methods</option>
                    @foreach(['direct_debit' => 'Bank mandate', 'card' => 'Card', 'checkout' => 'Paid in app', 'manual' => 'Manual'] as $k => $l)
                        <option value="{{ $k }}" @selected(request('channel') === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:tools>
        <div class="px-3 pt-3">
            <ul class="nav nav-tabs">
                @foreach(['all' => 'All', 'processing' => 'Processing', 'success' => 'Successful', 'failed' => 'Failed', 'pending' => 'Pending'] as $key => $label)
                    <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.credit.collections', ['status' => $key] + request()->only('channel')) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
        <div class="table-responsive">
            <table class="table gzc-table table-hover mb-0">
                <thead><tr><th>When</th><th>Customer</th><th>Statement</th><th>Method</th><th>Trigger</th><th class="text-end">Amount</th><th>Status</th><th>Reason / reference</th></tr></thead>
                <tbody>
                @forelse($rows as $at)
                    <tr>
                        <td class="small">{{ $at->created_at->format('j M Y H:i') }}</td>
                        <td><a href="{{ route('admin.credit.account', $at->account) }}">{{ $at->account->user?->full_name }}</a></td>
                        <td>@if($at->statement)<a href="{{ route('admin.credit.statement', $at->statement) }}">{{ $at->statement->number }}</a>@else — @endif</td>
                        <td>{{ $at->mandate?->label() ?? ['checkout' => 'Paid in app', 'manual' => 'Manual'][$at->channel] ?? $at->channel }}</td>
                        <td class="small">{{ $at->trigger }}@if($at->initiator && $at->trigger === 'admin') · {{ $at->initiator->full_name }}@endif</td>
                        <td class="text-end gzc-money">₦{{ number_format($at->amount, 2) }}</td>
                        <td><span class="badge bg-{{ $at->statusBadge() }}">{{ $at->status }}</span></td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($at->failure_reason, 70) }}<div class="text-muted font-monospace">{{ $at->reference }}</div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No debits here.</td></tr>
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
