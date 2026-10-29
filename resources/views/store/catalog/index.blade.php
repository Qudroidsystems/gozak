@extends('store.layout')

@section('title', $title . ' | ' . ($storeSettings['name'] ?? 'GozakMart'))
@if(!empty($canonical))@section('canonical', $canonical)@endif
@if(!empty($noindex))@push('meta')<meta name="robots" content="noindex,follow">@endpush @endif

@php
    $qs = fn (array $over) => url()->current() . '?' . http_build_query(array_filter(array_merge(request()->except('page'), $over), fn ($v) => $v !== null && $v !== ''));
    $sorts = ['popular' => 'Popularity', 'newest' => 'Newest arrivals', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'rating' => 'Product rating', 'discount' => 'Biggest discount'];
@endphp

@section('content')
<div class="wrap">
    <div class="crumbs">
        <a href="{{ route('store.home') }}">Home</a>
        @if(!empty($category))
            @if($category->parent) › <a href="{{ route('store.category', ['id' => $category->parent->id, 'slug' => \Illuminate\Support\Str::slug($category->parent->name)]) }}">{{ $category->parent->name }}</a>@endif
            › {{ $category->name }}
        @else
            › {{ $title }}
        @endif
    </div>

    <div class="listing">
        {{-- Filters --}}
        <aside class="filters box">
            <button type="button" class="btn btn-outline btn-block only-mobile" data-toggle-filters style="margin-bottom:8px">Filter &amp; sort</button>
            <div class="filters-body">
                @if(!empty($subcats) && $subcats->isNotEmpty())
                    <h3>Category</h3>
                    @foreach($subcats as $sc)
                        <a href="{{ route('store.category', ['id' => $sc->id, 'slug' => \Illuminate\Support\Str::slug($sc->name)]) }}">{{ $sc->name }}</a>
                    @endforeach
                @endif

                <h3>Price (₦)</h3>
                <form method="get" action="{{ url()->current() }}">
                    @foreach(request()->except(['min', 'max', 'page']) as $k => $v)
                        @if(is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <div class="price-filter">
                        <input type="number" name="min" min="0" placeholder="Min" value="{{ $min }}" aria-label="Minimum price">
                        <span>–</span>
                        <input type="number" name="max" min="0" placeholder="Max" value="{{ $max }}" aria-label="Maximum price">
                    </div>
                    <button class="btn btn-ghost" type="submit" style="padding-left:0">Apply</button>
                </form>

                <h3>Show only</h3>
                <a href="{{ $qs(['in_stock' => request()->boolean('in_stock') ? null : 1]) }}" class="{{ request()->boolean('in_stock') ? 'on' : '' }}">{{ request()->boolean('in_stock') ? '☑' : '☐' }} In stock</a>
                <a href="{{ $qs(['discount' => request()->boolean('discount') ? null : 1]) }}" class="{{ request()->boolean('discount') ? 'on' : '' }}">{{ request()->boolean('discount') ? '☑' : '☐' }} On discount</a>

                @if(request()->hasAny(['min', 'max', 'in_stock', 'discount']))
                    <a href="{{ url()->current() . (request('q') ? '?q=' . urlencode(request('q')) : '') }}" style="color:var(--brand);margin-top:10px">Clear filters</a>
                @endif
            </div>
        </aside>

        <section class="box">
            <div class="list-head">
                <div>
                    <h1>{{ $title }}</h1>
                    <small class="muted">{{ number_format($page->total()) }} {{ \Illuminate\Support\Str::plural('product', $page->total()) }} found</small>
                    @if(!empty($dealEnds))<div style="color:var(--brand);font-weight:500;margin-top:2px">Ends in <span class="countdown" data-countdown="{{ $dealEnds }}">…</span></div>@endif
                </div>
                <form class="sort" method="get" action="{{ url()->current() }}">
                    @foreach(request()->except(['sort', 'page']) as $k => $v)
                        @if(is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <label class="muted" for="sort">Sort by</label>
                    <select id="sort" name="sort" data-autosubmit>
                        @foreach($sorts as $k => $label)
                            <option value="{{ $k }}" @selected($sort === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($cards->isEmpty())
                <div class="empty">
                    <div class="big">🔍</div>
                    <h2>No products found</h2>
                    <p class="muted">Try a different search or remove some filters.</p>
                    <a href="{{ route('store.home') }}" class="btn btn-primary">Go to home page</a>
                </div>
            @else
                <div class="grid grid-4">
                    @foreach($cards as $card)
                        @include('store.partials.product-card', ['card' => $card])
                    @endforeach
                </div>

                @if($page->lastPage() > 1)
                    <nav class="pager" aria-label="Pages">
                        @if($page->onFirstPage())<span class="dis">‹</span>@else<a href="{{ $page->previousPageUrl() }}" rel="prev">‹</a>@endif
                        @foreach(range(max(1, $page->currentPage() - 2), min($page->lastPage(), $page->currentPage() + 2)) as $n)
                            @if($n === $page->currentPage())<span class="cur">{{ $n }}</span>@else<a href="{{ $page->url($n) }}">{{ $n }}</a>@endif
                        @endforeach
                        @if($page->hasMorePages())<a href="{{ $page->nextPageUrl() }}" rel="next">›</a>@else<span class="dis">›</span>@endif
                    </nav>
                @endif
            @endif
        </section>
    </div>
</div>
@endsection
