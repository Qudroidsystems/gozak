@extends('store.layout')

@section('title', 'Order details | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@php
    $num = $order->invoice_number ?? ('#' . strtoupper(substr($order->id, 0, 8)));
    $addr = $order->shippingAddress;
    $courier = $track['courier'] ?? null;
    $delivery = $track['delivery'] ?? null;
@endphp

@section('content')
<div class="wrap account">
    @include('store.account._nav')
    <div>
        <section class="box">
            <div class="sec-head">
                <h2><a href="{{ route('store.account.orders') }}" aria-label="Back to orders">←</a> Order {{ $num }}</h2>
                <span class="status status-{{ $order->status }}">{{ $track['status_label'] ?? ucfirst($order->status) }}</span>
            </div>
            <div style="padding:12px 16px">
                <div class="muted">Placed {{ optional($order->created_at)->format('d M Y, g:i A') }} · {{ $order->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $order->items->sum('quantity')) }}</div>
                <div style="font-size:18px;font-weight:700;margin-top:4px">₦{{ number_format($order->total_amount, 2) }}</div>

                @if(!$paid && $order->status !== 'cancelled')
                    <div class="flash flash-err" style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
                        <span>This order hasn't been paid yet. It will be prepared once payment is received.</span>
                        <form method="post" action="{{ route('store.account.order.pay', $order->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Pay now</button>
                        </form>
                    </div>
                @endif
            </div>
        </section>

        {{-- Tracking --}}
        @if(!empty($track['steps']) && $order->status !== 'cancelled')
            <section class="box section">
                <div class="sec-head"><h2>Tracking</h2></div>
                <ul class="timeline" style="padding-top:14px">
                    @foreach($track['steps'] as $s)
                        <li class="{{ $s['done'] ? 'done' : '' }}">
                            <strong>{{ $s['label'] }}</strong>
                            @if($s['at'])<small>{{ \Carbon\Carbon::parse($s['at'])->timezone('Africa/Lagos')->format('d M Y, g:i A') }}</small>@endif
                        </li>
                    @endforeach
                </ul>
                @if($delivery && !empty($delivery['delivery_code']))
                    <div class="flash flash-info" style="margin:0 16px 12px">
                        Delivery code: <strong style="letter-spacing:2px">{{ $delivery['delivery_code'] }}</strong>. Give it to the rider when your order arrives.
                        @if(!empty($delivery['rider_name']))<br>Rider: {{ $delivery['rider_name'] }}@if(!empty($delivery['rider_phone'])) · <a href="tel:{{ $delivery['rider_phone'] }}" style="text-decoration:underline">{{ $delivery['rider_phone'] }}</a>@endif @endif
                    </div>
                @endif
                @if($courier)
                    <div style="padding:0 16px 14px" class="muted">
                        Courier: <strong style="color:var(--ink)">{{ $courier['provider'] ?? 'Courier' }}</strong>
                        @if(!empty($courier['status_label'])) · {{ $courier['status_label'] }}@endif
                        @if(!empty($courier['marketplace'])) · provided on {{ $courier['marketplace'] }}@endif
                        @if(!empty($courier['tracking_url']))· <a href="{{ $courier['tracking_url'] }}" target="_blank" rel="noopener" style="color:var(--brand)">Track delivery</a>@endif
                    </div>
                @endif
                @if(!empty($track['can_confirm_receipt']))
                    <form method="post" action="{{ route('store.account.order.received', $order->id) }}" style="padding:0 16px 14px">
                        @csrf
                        <button type="submit" class="btn btn-outline">I've received my order</button>
                    </form>
                @endif
                <p class="muted" style="padding:0 16px 14px;font-size:12px">Follow your rider live on a map in the {{ $storeSettings['name'] }} app.</p>
            </section>
        @endif

        {{-- Items --}}
        <section class="box section">
            <div class="sec-head"><h2>Items in your order</h2></div>
            @foreach($order->items as $it)
                @php
                    $opts = $it->selected_variation;
                    if (is_string($opts)) $opts = json_decode($opts, true);
                    $img = \App\Models\ProductImage::publicUrl($it->image ?: $it->product?->thumbnail);
                @endphp
                <div class="order-row">
                    @if($img)<img src="{{ $img }}" alt="" loading="lazy">@else<div class="img-ph" style="width:64px;height:64px">·</div>@endif
                    <div>
                        @if($it->product)
                            <a href="{{ route('store.product', ['id' => $it->product_id, 'slug' => \Illuminate\Support\Str::slug($it->title ?: 'item')]) }}"><strong>{{ $it->title }}</strong></a>
                        @else
                            <strong>{{ $it->title }}</strong>
                        @endif
                        @if(is_array($opts) && $opts)<div class="muted" style="font-size:12px">@foreach($opts as $k => $v){{ $k }}: {{ $v }}@if(!$loop->last) · @endif @endforeach</div>@endif
                        <div class="muted" style="font-size:12px">Qty: {{ $it->quantity }}</div>
                    </div>
                    <strong>₦{{ number_format($it->price * $it->quantity, 2) }}</strong>
                </div>
            @endforeach
            <div style="padding:12px 16px">
                @php $itemsTotal = $order->items->sum(fn ($i) => $i->price * $i->quantity); @endphp
                <div class="sum-row"><span>Items</span><span>₦{{ number_format($itemsTotal, 2) }}</span></div>
                <div class="sum-row"><span>Delivery</span><span>₦{{ number_format($order->shipping_cost, 2) }}</span></div>
                @if((float) $order->tax_cost > 0)<div class="sum-row"><span>Tax</span><span>₦{{ number_format($order->tax_cost, 2) }}</span></div>@endif
                <div class="sum-row total"><span>Total</span><span>₦{{ number_format($order->total_amount, 2) }}</span></div>
            </div>
        </section>

        @if($addr)
            <section class="box section">
                <div class="sec-head"><h2>Delivery address</h2></div>
                <div style="padding:12px 16px">
                    <strong>{{ $addr->name }}</strong><br>
                    {{ $addr->street }}<br>
                    {{ $addr->lga ? $addr->lga . ', ' : '' }}{{ $addr->city }}, {{ $addr->state }}<br>
                    <span class="muted">{{ $addr->phone_number }}</span>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
