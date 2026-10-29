{{-- $card: one item from StoreCatalog::cards() --}}
<article class="card">
    <a href="{{ $card['url'] }}" class="card-link">
        <div class="card-img">
            @if($card['image'])
                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" decoding="async" width="300" height="300">
            @else
                <div class="img-ph">GozakMart</div>
            @endif
            @if($card['discount'])<span class="disc">-{{ $card['discount'] }}%</span>@endif
            @if(!$card['in_stock'])<span class="soldout">Sold out</span>@endif
        </div>
        <div class="card-body">
            <h3 class="card-title">{{ $card['title'] }}</h3>
            <div class="price">₦{{ number_format($card['price']) }}</div>
            @if($card['old_price'])<div class="old">₦{{ number_format($card['old_price']) }}</div>@endif
            @if($card['reviews'] > 0)
                <div class="stars" aria-label="Rated {{ $card['rating'] }} out of 5">
                    <span class="stars-fill" style="--r: {{ $card['rating'] * 20 }}%"></span>
                    <small>({{ $card['reviews'] }})</small>
                </div>
            @endif
        </div>
    </a>
    @if($card['in_stock'])
        @if($card['variable'])
            <a href="{{ $card['url'] }}" class="btn btn-primary btn-sm card-btn">Choose options</a>
        @else
            <button type="button" class="btn btn-primary btn-sm card-btn" data-add-to-cart="{{ $card['id'] }}">Add to cart</button>
        @endif
    @endif
</article>
