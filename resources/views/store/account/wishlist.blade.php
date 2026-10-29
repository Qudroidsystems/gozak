@extends('store.layout')

@section('title', 'Saved items | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@section('content')
<div class="wrap account">
    @include('store.account._nav')
    <section class="box">
        <div class="sec-head"><h2>Saved items ({{ $cards->count() }})</h2></div>
        @if($cards->isEmpty())
            <div class="empty">
                <div class="big">♡</div>
                <h2>You haven't saved an item yet!</h2>
                <p class="muted">Found something you like? Tap the heart on the product page to save it for later.</p>
                <a href="{{ route('store.home') }}" class="btn btn-primary">Continue shopping</a>
            </div>
        @else
            <div class="grid grid-4">
                @foreach($cards as $card)
                    <div>
                        @include('store.partials.product-card', ['card' => $card])
                        <form method="post" action="{{ route('store.account.wishlist.toggle') }}" style="text-align:center">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $card['id'] }}">
                            <button type="submit" class="btn btn-ghost">Remove</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
