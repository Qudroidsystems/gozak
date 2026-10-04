@extends('layouts.master')

@section('title', 'Credit applications')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => 'Credit applications', 'subtitle' => 'Review who gets Gozak Credit and how much.'])

    <x-cb.card :flush="true">
        <x-slot:tools>
            <form class="d-flex gap-2" method="GET">
                <input type="hidden" name="status" value="{{ $status }}">
                <input class="form-control form-control-sm" name="q" value="{{ request('q') }}" placeholder="Name, phone or email">
                <button class="btn btn-sm btn-light">Search</button>
            </form>
        </x-slot:tools>
        <div class="px-3 pt-3">
            <ul class="nav nav-tabs">
                @foreach(['pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
                    <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('admin.credit.applications', ['status' => $key]) }}">{{ $label }}
                        @if($key !== 'all')<span class="badge bg-light text-dark ms-1">{{ $counts[$key] ?? 0 }}</span>@endif</a></li>
                @endforeach
            </ul>
        </div>
        <div class="table-responsive">
            <table class="table gzc-table table-hover mb-0">
                <thead><tr><th>Applicant</th><th>Applied</th><th>Income</th><th>Employment</th><th class="text-end">Requested</th><th class="text-end">Suggested</th><th>Score</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($rows as $app)
                    <tr>
                        <td><div class="fw-semibold">{{ $app->full_name }}</div><div class="small text-muted">{{ $app->user?->email }} · {{ $app->phone }}</div></td>
                        <td>{{ $app->created_at->format('j M, H:i') }}<div class="small text-muted">{{ $app->created_at->diffForHumans() }}</div></td>
                        <td>{{ $app->incomeLabel() }}</td>
                        <td>{{ $app->employmentLabel() }}</td>
                        <td class="text-end gzc-money">₦{{ number_format($app->requested_limit) }}</td>
                        <td class="text-end gzc-money">₦{{ number_format($app->suggested_limit) }}</td>
                        <td><span class="badge bg-{{ $app->risk_score >= 60 ? 'success' : ($app->risk_score >= 40 ? 'warning' : 'danger') }}">{{ $app->risk_score }}/100</span></td>
                        <td>
                            <span class="badge bg-{{ ['pending' => 'info', 'approved' => 'success', 'rejected' => 'danger'][$app->status] ?? 'secondary' }}">{{ ucfirst($app->status) }}</span>
                            @if($app->reviewer)<div class="small text-muted">by {{ $app->reviewer->full_name }}</div>@endif
                        </td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('admin.credit.application', $app) }}">{{ $app->status === 'pending' ? 'Review' : 'View' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">No applications here.</td></tr>
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
