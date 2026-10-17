@extends('rider.layout')

@section('title', 'My runs')

@section('content')
    @if($problem)
        <div class="rd-card text-center py-4">
            <i class="ri-user-unfollow-line fs-1 text-muted d-block mb-2"></i>
            {{ $problem }}
        </div>
    @else
        <h5 class="mb-3">Hello {{ \Illuminate\Support\Str::before($rider->name, ' ') }} 👋</h5>
        @forelse($runs as $b)
            <a href="{{ route('rider.run', $b->id) }}" class="text-decoration-none text-reset">
                <div class="rd-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="rd-route fs-5">{{ $b->route_code }}</span>
                        <span class="badge bg-{{ $b->statusBadge() }} fs-6">{{ $b->status === 'dispatched' ? 'On the road' : $b->statusLabel() }}</span>
                    </div>
                    <div class="fw-semibold">{{ $b->zone?->name ?? 'Delivery run' }} · {{ $b->orders_count }} stops · {{ $b->parcels_count }} parcels</div>
                    @if($b->status === 'dispatched')
                        <div class="progress mt-2" style="height:8px;"><div class="progress-bar bg-success" style="width: {{ $b->orders_count ? round($b->delivered_count / $b->orders_count * 100) : 0 }}%"></div></div>
                        <div class="rd-muted mt-1">{{ $b->delivered_count }} of {{ $b->orders_count }} delivered</div>
                    @elseif(in_array($b->status, ['draft', 'loading']))
                        <div class="rd-muted mt-1">Being loaded at {{ $b->branch?->name ?? 'the store' }}. You'll see the stops here once you're dispatched.</div>
                    @else
                        <div class="rd-muted mt-1">Closed {{ $b->completed_at?->format('g:i A') }}</div>
                    @endif
                    <div class="rd-muted mt-1 font-monospace">{{ $b->code }}</div>
                </div>
            </a>
        @empty
            <div class="rd-card text-center py-5 text-muted">
                <i class="ri-cup-line fs-1 d-block mb-2"></i>
                No runs assigned to you right now.
            </div>
        @endforelse
        <a href="{{ route('rider.index') }}" class="btn btn-outline-secondary w-100"><i class="ri-refresh-line"></i> Refresh</a>
    @endif
@endsection
