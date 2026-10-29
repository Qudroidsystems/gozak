@extends('store.layout')

@section('title', 'Cart | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@section('content')
<div class="wrap">
    @if(empty($cart['lines']))
        <div class="box empty">
            <div class="big">🛒</div>
            <h2>Your cart is empty!</h2>
            <p class="muted">Browse our categories and discover our best deals!</p>
            <a href="{{ route('store.home') }}" class="btn btn-primary">Start shopping</a>
        </div>
    @else
        <div class="two-col">
            <section class="box">
                <div class="sec-head"><h2>Cart ({{ $cart['count'] }})</h2></div>
                @foreach($cart['lines'] as $l)
                    <div class="cart-line">
                        <a href="{{ $l['url'] }}">
                            @if($l['image'])<img src="{{ $l['image'] }}" alt="" loading="lazy">@else<div class="img-ph" style="width:72px;height:72px">·</div>@endif
                        </a>
                        <div>
                            <a href="{{ $l['url'] }}" class="t">{{ $l['title'] }}</a>
                            @if($l['options'])
                                <div class="o">@foreach($l['options'] as $k => $v){{ $k }}: <strong>{{ $v }}</strong>@if(!$loop->last) · @endif @endforeach</div>
                            @endif
                            @if(isset($cart['problems'][$l['key']]))
                                <div class="err">{{ $cart['problems'][$l['key']] }}</div>
                            @elseif($l['available'] > 0 && $l['available'] <= 5)
                                <div class="o" style="color:var(--brand)">Only {{ $l['available'] }} left</div>
                            @endif
                        </div>
                        <div class="r">
                            <div class="price">₦{{ number_format($l['price']) }}</div>
                            @if($l['old_price'])
                                <div class="old">₦{{ number_format($l['old_price']) }}</div>
                                <span class="disc-inline" style="background:var(--brand-soft);color:var(--brand);font-size:12px;padding:1px 4px">-{{ round(($l['old_price'] - $l['price']) / $l['old_price'] * 100) }}%</span>
                            @endif
                        </div>
                        <div class="cart-line-actions">
                            <form method="post" action="{{ route('store.cart.remove') }}">
                                @csrf
                                <input type="hidden" name="key" value="{{ $l['key'] }}">
                                <button type="submit" class="btn btn-ghost">🗑 Remove</button>
                            </form>
                            <form method="post" action="{{ route('store.cart.update') }}" class="qty-form">
                                @csrf
                                <input type="hidden" name="key" value="{{ $l['key'] }}">
                                <div class="qty">
                                    <button type="submit" name="step" value="-1" aria-label="Less" @disabled($l['quantity'] <= 1)>−</button>
                                    <input type="number" name="quantity" value="{{ $l['quantity'] }}" min="1" max="{{ max(1, min(1000, $l['available'] ?: 1000)) }}" aria-label="Quantity" onchange="this.form.submit()">
                                    <button type="submit" name="step" value="1" aria-label="More">+</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </section>

            <aside class="box summary">
                <h2>Cart summary</h2>
                <div class="sum-row"><span>Subtotal</span><strong>₦{{ number_format($cart['subtotal'], 2) }}</strong></div>
                <p class="note">Delivery fee and {{ $cart['tax_enabled'] ? 'tax are' : 'total are' }} worked out at checkout.
                    @if($cart['free_shipping_threshold'] > 0 && $cart['subtotal'] < $cart['free_shipping_threshold'])
                        Add <strong>₦{{ number_format($cart['free_shipping_threshold'] - $cart['subtotal']) }}</strong> more for free GozakMart Delivery.
                    @endif
                </p>
                @if($cart['problems'])
                    <div class="flash flash-err" style="margin-top:10px">Some items need your attention before checkout.</div>
                    <button class="btn btn-primary btn-block btn-lg" disabled>Checkout (₦{{ number_format($cart['subtotal']) }})</button>
                @else
                    <a href="{{ route('store.checkout') }}" class="btn btn-primary btn-block btn-lg" style="margin-top:12px">Checkout (₦{{ number_format($cart['subtotal']) }})</a>
                @endif
                <p class="note">🔒 Secure payment with Paystack / OPay</p>
            </aside>
        </div>
    @endif
</div>
@endsection
