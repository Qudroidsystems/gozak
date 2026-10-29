@extends('store.layout')

@section('title', 'My orders | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@section('content')
<div class="wrap account">
    @include('store.account._nav')
    <section class="box">
        <div class="sec-head"><h2>Orders</h2></div>
        @forelse($orders as $o)
            @php
                $first = $o->items->first();
                $img = $first ? \App\Models\ProductImage::publicUrl($first->image ?: $first->product?->thumbnail) : null;
                $paid = $o->isPaid();
            @endphp
            <a class="order-row" href="{{ route('store.account.order', $o->id) }}">
                @if($img)<img src="{{ $img }}" alt="" loading="lazy">@else<div class="img-ph" style="width:64px;height:64px">·</div>@endif
                <div>
                    <strong>{{ $first?->title ?? 'Order' }}@if($o->items->count() > 1) <span class="muted">+ {{ $o->items->count() - 1 }} more</span>@endif</strong>
                    <div class="muted" style="font-size:12px">Order {{ $o->invoice_number ?? ('#' . strtoupper(substr($o->id, 0, 8))) }} · {{ optional($o->created_at)->format('d M Y') }}</div>
                    <div style="margin-top:4px">
                        <span class="status status-{{ $o->status }}">{{ ucfirst($o->status) }}</span>
                        @if(!$paid && $o->status !== 'cancelled')<span class="status status-pending">Awaiting payment</span>@endif
                    </div>
                </div>
                <div class="r" style="text-align:right">
                    <strong>₦{{ number_format($o->total_amount, 2) }}</strong>
                    <div style="color:var(--brand);font-size:12px;margin-top:4px">See details ›</div>
                </div>
            </a>
        @empty
            <div class="empty">
                <div class="big">📦</div>
                <h2>You haven't placed any orders yet!</h2>
                <p class="muted">All your orders, from the website and the app, will show here.</p>
                <a href="{{ route('store.home') }}" class="btn btn-primary">Start shopping</a>
            </div>
        @endforelse

        @if($orders->lastPage() > 1)
            <nav class="pager">
                @if($orders->onFirstPage())<span class="dis">‹</span>@else<a href="{{ $orders->previousPageUrl() }}">‹</a>@endif
                <span class="cur">{{ $orders->currentPage() }}</span>
                @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">›</a>@else<span class="dis">›</span>@endif
            </nav>
        @endif
    </section>
</div>
@endsection
