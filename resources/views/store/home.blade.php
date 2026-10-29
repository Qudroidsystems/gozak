@extends('store.layout')

@section('canonical', route('store.home'))

@php
    $sections = [
        ['key' => 'top',      'title' => 'Top selling items',  'url' => route('store.search', ['sort' => 'popular'])],
        ['key' => 'sale',     'title' => 'Top deals',          'url' => route('store.deals')],
        ['key' => 'new',      'title' => 'New arrivals',       'url' => route('store.search', ['sort' => 'newest'])],
        ['key' => 'trending', 'title' => 'Trending now',       'url' => route('store.search', ['sort' => 'popular'])],
        ['key' => 'featured', 'title' => 'Recommended for you','url' => route('store.search')],
    ];
@endphp

@section('content')
<div class="wrap">
    {{-- Hero: categories · slider · side tiles --}}
    <section class="hero">
        <nav class="cat-menu box" aria-label="Categories">
            <ul>
                <li><a href="{{ route('store.deals') }}">⚡ <span>Flash Sales</span></a></li>
                @foreach(($storeCategories ?? collect())->take(12) as $c)
                    <li>
                        <a href="{{ $c['url'] }}">{{ $c['name'] }}</a>
                        @if(!empty($c['children']))
                            <div class="cat-sub">
                                <h4>{{ $c['name'] }}</h4>
                                @foreach($c['children'] as $ch)
                                    <a href="{{ $ch['url'] }}">{{ $ch['name'] }}</a>
                                @endforeach
                                <a href="{{ $c['url'] }}" style="color:var(--brand)">See all {{ $c['name'] }} →</a>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="slider box" data-slider>
            <div class="slides">
                @forelse($banners as $b)
                    <a class="slide" href="{{ $b['url'] }}"><img src="{{ $b['image'] }}" alt="{{ $b['title'] ?? 'Offer' }}" @if(!$loop->first) loading="lazy" @endif></a>
                @empty
                    <div class="slide">
                        <div class="slide-txt">
                            <h2>Shop smart. Get it delivered.</h2>
                            <p>Groceries, fashion, phones and more, at great prices, delivered to your door.</p>
                            <div><a href="{{ route('store.deals') }}" class="btn btn-light">Shop Flash Sales</a></div>
                        </div>
                    </div>
                @endforelse
            </div>
            <div class="dots"></div>
        </div>

        <div class="side-tiles">
            <a class="tile box" href="{{ route('store.deals') }}">
                <span class="ico">⚡</span>
                <strong>Flash Sales</strong>
                <span class="muted">Big discounts for a short time</span>
            </a>
            <a class="tile box" href="{{ $storeSettings['app_url'] }}" target="_blank" rel="noopener">
                <span class="ico">📱</span>
                <strong>Get the app</strong>
                <span class="muted">Scan &amp; Shop in our stores and track your rider live</span>
            </a>
        </div>
    </section>

    {{-- Category bubbles (helpful on phones, where the left menu is hidden) --}}
    @if(($storeCategories ?? collect())->isNotEmpty())
        <section class="section box mobile-block">
            <div class="bubbles">
                @foreach($storeCategories->take(12) as $c)
                    <a class="bubble" href="{{ $c['url'] }}">
                        <div class="bubble-img">
                            @if($c['image'])<img src="{{ $c['image'] }}" alt="" loading="lazy">@else{{ mb_substr($c['name'], 0, 1) }}@endif
                        </div>
                        {{ $c['name'] }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Flash sales --}}
    @if($deals->isNotEmpty())
        <section class="section box">
            <div class="sec-head hot">
                <h2>⚡ Flash Sales</h2>
                @if($dealEnds)<span>Time left: <span class="countdown" data-countdown="{{ $dealEnds }}">…</span></span>@endif
                <a href="{{ route('store.deals') }}">See all ›</a>
            </div>
            <div class="rail">
                @foreach($deals as $card)
                    @include('store.partials.product-card', ['card' => $card])
                @endforeach
            </div>
        </section>
    @endif

    @foreach($sections as $sec)
        @if(${$sec['key']}->isNotEmpty())
            <section class="section box">
                <div class="sec-head">
                    <h2>{{ $sec['title'] }}</h2>
                    <a href="{{ $sec['url'] }}">See all ›</a>
                </div>
                <div class="rail">
                    @foreach(${$sec['key']} as $card)
                        @include('store.partials.product-card', ['card' => $card])
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    {{-- Official brands --}}
    @if($brands->isNotEmpty())
        <section class="section box">
            <div class="sec-head"><h2>Official stores</h2></div>
            <div class="bubbles">
                @foreach($brands as $b)
                    <a class="bubble" href="{{ $b['url'] }}">
                        <div class="bubble-img" style="border-radius:8px;background:#fff;border:1px solid var(--line)">
                            @if($b['logo'])<img src="{{ $b['logo'] }}" alt="{{ $b['name'] }}" loading="lazy" style="object-fit:contain">@else{{ mb_substr($b['name'], 0, 1) }}@endif
                        </div>
                        {{ $b['name'] }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($top->isEmpty() && $deals->isEmpty())
        <div class="box empty section">
            <div class="big">🛒</div>
            <h2>Products are on their way</h2>
            <p class="muted">Check back soon.</p>
        </div>
    @endif
</div>
@endsection
