<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($storeSettings['name'] ?? 'GozakMart') . ' | Online Shopping in Nigeria')</title>
    <meta name="description" content="@yield('description', 'Shop groceries, fashion, phones, electronics and more on ' . ($storeSettings['name'] ?? 'GozakMart') . '. Fast delivery across Nigeria. Pay securely online.')">
    <meta name="theme-color" content="#FF5722">
    @hasSection('canonical')<link rel="canonical" href="@yield('canonical')">@endif
    @stack('meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('store/store.css') }}?v=1">
    @stack('styles')
</head>
<body>
@php $cats = $storeCategories ?? collect(); @endphp

{{-- Thin promo bar --}}
<div class="topbar">
    <div class="wrap topbar-in">
        <span>🚚 Fast delivery across Nigeria · Pay securely with card or bank transfer</span>
        <a href="{{ $storeSettings['app_url'] }}" target="_blank" rel="noopener">Get the app</a>
    </div>
</div>

{{-- Header --}}
<header class="hdr">
    <div class="wrap hdr-in">
        <button class="icon-btn only-mobile" type="button" data-open-menu aria-label="Open categories">
            <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>

        <a href="{{ route('store.home') }}" class="logo" aria-label="{{ $storeSettings['name'] }} home">
            @if($storeSettings['logo'])
                <img src="{{ $storeSettings['logo'] }}" alt="{{ $storeSettings['name'] }}">
            @else
                <span class="logo-text">GOZAK<b>MART</b></span><span class="logo-star">★</span>
            @endif
        </a>

        <form class="search" action="{{ route('store.search') }}" method="get" role="search">
            <svg class="search-ico" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="m20 20-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products, brands and categories" aria-label="Search" autocomplete="off">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <nav class="hdr-actions">
            <div class="dd">
                <button type="button" class="hdr-link" data-dd>
                    <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2" fill="none"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                    <span class="only-desktop">{{ $storeCustomer ? 'Hi, ' . \Illuminate\Support\Str::limit($storeCustomer->first_name ?: 'there', 12) : 'Account' }}</span>
                    <svg class="only-desktop" width="12" height="12" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" fill="none"/></svg>
                </button>
                <div class="dd-menu">
                    @if($storeCustomer)
                        <a href="{{ route('store.account.orders') }}">📦 Orders</a>
                        <a href="{{ route('store.account.wishlist') }}">♡ Saved items</a>
                        <a href="{{ route('store.account.addresses') }}">📍 Addresses</a>
                        <form method="post" action="{{ route('store.logout') }}">@csrf<button type="submit" class="dd-btn">Sign out</button></form>
                    @else
                        <a href="{{ route('store.login') }}" class="btn btn-primary btn-block">Sign in</a>
                        <a href="{{ route('store.register') }}">Create an account</a>
                        <hr>
                        <a href="{{ route('store.account.orders') }}">📦 Orders</a>
                        <a href="{{ route('store.account.wishlist') }}">♡ Saved items</a>
                    @endif
                </div>
            </div>
            <a class="hdr-link only-desktop" href="{{ route('store.help') }}">
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6V14M12 17.5v.01" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
                <span>Help</span>
            </a>
            <a class="hdr-link cart-link" href="{{ route('store.cart') }}" aria-label="Cart">
                <span class="cart-ico">
                    <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4" fill="currentColor"/><circle cx="17" cy="20" r="1.4" fill="currentColor"/></svg>
                    <span class="badge" data-cart-count @if(!$storeCartCount) hidden @endif>{{ $storeCartCount }}</span>
                </span>
                <span class="only-desktop">Cart</span>
            </a>
        </nav>
    </div>
</header>

{{-- Mobile category drawer --}}
<div class="drawer" data-menu hidden>
    <div class="drawer-panel" role="dialog" aria-label="Categories">
        <div class="drawer-head">
            <strong>Categories</strong>
            <button type="button" class="icon-btn" data-close-menu aria-label="Close">✕</button>
        </div>
        <a class="drawer-link" href="{{ route('store.deals') }}">⚡ Flash Sales</a>
        @foreach($cats as $c)
            <a class="drawer-link" href="{{ $c['url'] }}">{{ $c['name'] }}</a>
        @endforeach
        <hr>
        @if($storeCustomer)
            <a class="drawer-link" href="{{ route('store.account.orders') }}">My orders</a>
        @else
            <a class="drawer-link" href="{{ route('store.login') }}">Sign in</a>
        @endif
        <a class="drawer-link" href="{{ route('store.help') }}">Help</a>
    </div>
</div>

<main class="main">
    <div class="wrap">
        @if(session('store_success'))
            <div class="flash flash-ok" role="status">{{ session('store_success') }}</div>
        @endif
        @if(session('store_error'))
            <div class="flash flash-err" role="alert">{{ session('store_error') }}</div>
        @endif
        @if(session('store_notice'))
            <div class="flash flash-info" role="status">{{ session('store_notice') }}</div>
        @endif
    </div>
    @yield('content')
</main>

<footer class="ftr">
    <div class="ftr-app">
        <div class="wrap ftr-app-in">
            <div>
                <strong>Shop faster on the {{ $storeSettings['name'] }} app</strong>
                <span>Scan &amp; Shop in our stores, track your rider live, and get app-only deals.</span>
            </div>
            <a class="btn btn-light" href="{{ $storeSettings['app_url'] }}" target="_blank" rel="noopener">Get it on Google Play</a>
        </div>
    </div>
    <div class="wrap ftr-cols">
        <div>
            <h4>Need help?</h4>
            <a href="{{ route('store.help') }}">Help centre</a>
            <a href="{{ route('store.account.orders') }}">Track your order</a>
            @if($storeSettings['phone'])<a href="tel:{{ preg_replace('/\s+/', '', $storeSettings['phone']) }}">Call {{ $storeSettings['phone'] }}</a>@endif
            @if($storeSettings['email'])<a href="mailto:{{ $storeSettings['email'] }}">{{ $storeSettings['email'] }}</a>@endif
        </div>
        <div>
            <h4>Shop</h4>
            <a href="{{ route('store.deals') }}">Flash Sales</a>
            @foreach($cats->take(5) as $c)<a href="{{ $c['url'] }}">{{ $c['name'] }}</a>@endforeach
        </div>
        <div>
            <h4>About</h4>
            <a href="{{ route('legal.privacy') }}">Privacy policy</a>
            <a href="{{ route('legal.terms') }}">Terms of use</a>
            <a href="{{ route('legal.delete-account') }}">Delete my account</a>
        </div>
        <div>
            <h4>Payment</h4>
            <p class="muted">Pay securely with card, bank transfer or USSD through Paystack / OPay. We never see your card details.</p>
        </div>
    </div>
    <div class="ftr-bottom">
        <div class="wrap">© {{ date('Y') }} {{ $storeSettings['name'] }}@if($storeSettings['address']) · {{ $storeSettings['address'] }}@endif</div>
    </div>
</footer>

<div class="toast" data-toast role="status" aria-live="polite" hidden></div>

<script>window.STORE = { cartAdd: @json(route('store.cart.add')), csrf: @json(csrf_token()) };</script>
<script src="{{ asset('store/store.js') }}?v=1" defer></script>
@stack('scripts')
</body>
</html>
