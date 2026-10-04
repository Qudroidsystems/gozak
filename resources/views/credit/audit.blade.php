@extends('layouts.master')

@section('title', 'Credit activity log')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => 'Activity log', 'subtitle' => 'Who did what, and when — approvals, limits, debits, waivers and settings.'])

    <x-cb.card :flush="true">
        <x-slot:tools>
            <form class="d-flex gap-2" method="GET">
                <select class="form-select form-select-sm" name="action" onchange="this.form.submit()">
                    <option value="">All activity</option>
                    @foreach(['application' => 'Applications', 'limit' => 'Limit changes', 'status' => 'Status changes', 'payment' => 'Payments', 'collection' => 'Debit attempts', 'late_fee' => 'Late fees', 'adjustment' => 'Adjustments', 'mandate' => 'Bank / card links', 'statement' => 'Statements', 'settings' => 'Settings'] as $k => $l)
                        <option value="{{ $k }}" @selected(request('action') === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </form>
        </x-slot:tools>
        <div class="table-responsive">
            <table class="table gzc-table table-sm mb-0">
                <thead><tr><th>When</th><th>Customer</th><th>What happened</th><th>By</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($rows as $log)
                    <tr>
                        <td class="small text-nowrap">{{ $log->created_at->format('j M Y H:i') }}</td>
                        <td>@if($log->account)<a href="{{ route('admin.credit.account', $log->account) }}">{{ $log->customer?->full_name }}</a>@else{{ $log->customer?->full_name ?? '—' }}@endif</td>
                        <td>{{ $log->summary }}</td>
                        <td class="small">{{ $log->actor?->full_name ?? 'System' }}</td>
                        <td class="small text-muted font-monospace">{{ $log->action }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No activity yet.</td></tr>
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
