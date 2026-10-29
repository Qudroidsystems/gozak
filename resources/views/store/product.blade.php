@extends('store.layout')

@php
    use Illuminate\Support\Str;
    $title = $p['title'] ?? 'Product';
    $media = collect($p['media'] ?? []);
    if ($media->isEmpty()) {
        $urls = collect([$p['thumbnail'] ?? null])->merge($p['images'] ?? [])->filter()->unique()->values();
        $media = $urls->map(fn ($u, $i) => ['id' => 'i' . $i, 'type' => 'image', 'url' => $u, 'thumb_url' => $u, 'poster_url' => null, 'attribute_name' => null, 'attribute_value' => null]);
    } elseif (!empty($p['thumbnail']) && !$media->contains('url', $p['thumbnail'])) {
        // Keep the cover photo, after any videos (Temu/Jumia style)
        $videos = $media->where('type', 'video');
        $media = $videos->concat([['id' => 'thumb', 'type' => 'image', 'url' => $p['thumbnail'], 'thumb_url' => $p['thumbnail'], 'poster_url' => null, 'attribute_name' => null, 'attribute_value' => null]])
            ->concat($media->where('type', '!=', 'video'))->values();
    }
    $firstPhoto = $media->search(fn ($m) => $m['type'] === 'image');
    $start = $firstPhoto === false ? 0 : $firstPhoto;
    $attrs = $p['product_attributes'] ?? [];
    $vars  = $p['product_variations'] ?? [];
    $isVariable = ($p['product_type'] ?? '') === 'variable' && count($vars) > 0;
    $price = (float) ($p['price'] ?? 0);
    $sale  = !empty($p['sale_price']) && (float) $p['sale_price'] < $price ? (float) $p['sale_price'] : null;
    $rating = (float) ($p['average_rating'] ?? 0);
    $reviewCount = (int) ($p['review_count'] ?? 0);
    $desc = trim((string) ($p['description'] ?? ''));
    $specs = $p['specifications'] ?? [];
    $guide = $p['size_guide'] ?? null;
    $ogImage = $media->firstWhere('type', 'image')['url'] ?? ($p['thumbnail'] ?? null);
@endphp

@section('title', $title . ' | ' . ($storeSettings['name'] ?? 'GozakMart'))
@section('description', Str::limit(strip_tags($desc) ?: $title, 155))
@section('canonical', $canonical)

@push('meta')
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $title }}">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta property="og:url" content="{{ $canonical }}">
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $title,
        'image' => $ogImage,
        'description' => Str::limit(strip_tags($desc), 300) ?: null,
        'sku' => $p['sku'] ?? null,
        'brand' => !empty($p['brand']['name']) ? ['@type' => 'Brand', 'name' => $p['brand']['name']] : null,
        'aggregateRating' => $reviewCount > 0 ? ['@type' => 'AggregateRating', 'ratingValue' => $rating, 'reviewCount' => $reviewCount] : null,
        'offers' => ['@type' => 'Offer', 'priceCurrency' => 'NGN', 'price' => $sale ?? $price, 'availability' => ((int) ($p['stock'] ?? 0)) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'url' => $canonical],
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<div class="wrap" id="pdp">
    <div class="crumbs">
        <a href="{{ route('store.home') }}">Home</a>
        @if($category) › <a href="{{ route('store.category', ['id' => $category->id, 'slug' => Str::slug($category->name)]) }}">{{ $category->name }}</a>@endif
        › {{ Str::limit($title, 60) }}
    </div>

    <div class="pdp">
        <div>
            <section class="box pdp-main">
                {{-- Gallery --}}
                <div>
                    <div class="gallery-main" data-gallery-main role="button" tabindex="0" aria-label="Open photo"></div>
                    @if($media->count() > 1)
                        <div class="thumbs" role="list">
                            @foreach($media as $i => $m)
                                <button type="button" data-thumb="{{ $i }}" aria-label="{{ $m['type'] === 'video' ? 'Video' : 'Photo ' . ($i + 1) }}">
                                    @if(!empty($m['thumb_url']) || !empty($m['poster_url']))
                                        <img src="{{ $m['thumb_url'] ?: $m['poster_url'] }}" alt="" loading="lazy">
                                    @endif
                                    @if($m['type'] === 'video')<span class="play">▶</span>@endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Info + buy box --}}
                <div class="pdp-info">
                    <h1>{{ $title }}</h1>
                    @if(!empty($p['brand']['name']))
                        <div class="brand-line">Brand: <a href="{{ route('store.brand', ['id' => $p['brand']['id'], 'slug' => Str::slug($p['brand']['name']) ?: 'brand']) }}">{{ $p['brand']['name'] }}</a></div>
                    @endif
                    @if($reviewCount > 0)
                        <a href="#reviews" class="stars" aria-label="Rated {{ $rating }} out of 5"><span class="stars-fill" style="--r: {{ $rating * 20 }}%"></span><small>({{ $reviewCount }} verified {{ Str::plural('rating', $reviewCount) }})</small></a>
                    @endif

                    <div class="pdp-price">
                        @if($dealPct)
                            <div class="deal-strip"><span>⚡ Flash Sale</span>@if($dealEnds)<span>Ends in <span class="countdown" data-countdown="{{ $dealEnds }}">…</span></span>@endif</div>
                        @endif
                        <div><span class="now" data-price>₦{{ number_format($sale ?? $price) }}</span></div>
                        <div data-was-wrap @if(!$sale) hidden @endif>
                            <span class="was" data-was>₦{{ number_format($price) }}</span>
                            <span class="disc-inline" data-disc>-{{ $sale && $price > 0 ? round(($price - $sale) / $price * 100) : 0 }}%</span>
                        </div>
                        <div class="stock-line" data-stock-line></div>
                        <small class="muted">Delivery fee is shown at checkout.</small>
                    </div>

                    {{-- Options --}}
                    @if($isVariable)
                        @foreach($attrs as $a)
                            <div class="opt" data-opt="{{ $a['name'] }}">
                                <div class="opt-name">{{ $a['name'] }}: <b data-opt-label></b>
                                    @if($guide && preg_match('/size/i', $a['name']))
                                        <a href="#size-guide" class="btn-ghost" style="float:right;font-size:12px">Size guide</a>
                                    @endif
                                </div>
                                <div class="opt-vals">
                                    @foreach($a['options'] ?? array_map(fn ($v) => ['value' => $v], $a['values'] ?? []) as $o)
                                        @php $type = $a['display_type'] ?? 'button'; @endphp
                                        <button type="button"
                                                class="opt-val {{ $type === 'image' && !empty($o['image']) ? 'img' : '' }}"
                                                data-value="{{ $o['value'] }}"
                                                title="{{ $o['value'] }}">
                                            @if($type === 'image' && !empty($o['image']))
                                                <img src="{{ $o['image'] }}" alt="{{ $o['value'] }}" loading="lazy">
                                            @elseif($type === 'color' && !empty($o['color_hex']))
                                                <span class="sw" style="background: {{ preg_match('/^#[0-9A-Fa-f]{6,8}$/', $o['color_hex']) ? $o['color_hex'] : '#ccc' }}"></span>{{ $o['value'] }}
                                            @else
                                                {{ $o['value'] }}
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif

                    <div class="buy-row">
                        <div class="qty" aria-label="Quantity">
                            <button type="button" data-qty-step="-1" aria-label="Less">−</button>
                            <input type="number" value="1" min="1" max="99" data-qty aria-label="Quantity">
                            <button type="button" data-qty-step="1" aria-label="More">+</button>
                        </div>
                        <button type="button" class="btn btn-primary btn-lg" data-buy>Add to cart</button>
                        <form method="post" action="{{ route('store.account.wishlist.toggle') }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $p['id'] }}">
                            <button type="submit" class="btn btn-outline btn-lg" title="{{ $saved ? 'Remove from saved items' : 'Save for later' }}" aria-label="{{ $saved ? 'Remove from saved items' : 'Save for later' }}">{{ $saved ? '♥' : '♡' }}</button>
                        </form>
                    </div>
                </div>
            </section>

            {{-- Details tabs --}}
            <section class="box section">
                <nav class="tabs">
                    <a href="#details" class="on">Product details</a>
                    @if($specs)<a href="#specs">Specifications</a>@endif
                    @if($guide)<a href="#size-guide">Size guide</a>@endif
                    <a href="#reviews">Reviews ({{ $reviewCount }})</a>
                </nav>
                <div class="desc" id="details">
                    @if($desc !== ''){!! nl2br(e($desc)) !!}@else<span class="muted">No description yet.</span>@endif
                </div>
            </section>

            @if($specs)
                <section class="box section" id="specs">
                    <div class="sec-head"><h2>Specifications</h2></div>
                    <table class="specs">
                        @foreach($specs as $s)
                            <tr><td>{{ $s['label'] }}</td><td>{{ $s['value'] }}</td></tr>
                        @endforeach
                    </table>
                </section>
            @endif

            @if($guide)
                <section class="box section" id="size-guide">
                    <div class="sec-head"><h2>Size guide @if(!empty($guide['unit']))<small class="muted">({{ $guide['unit'] }})</small>@endif</h2></div>
                    <div class="size-guide" style="padding:16px">
                        <table>
                            <tr>@foreach($guide['columns'] as $c)<th>{{ $c }}</th>@endforeach</tr>
                            @foreach($guide['rows'] as $r)
                                <tr>@foreach($guide['columns'] as $i => $c)<td>{{ $r[$i] ?? '' }}</td>@endforeach</tr>
                            @endforeach
                        </table>
                        @if(!empty($guide['note']))<p class="muted">{{ $guide['note'] }}</p>@endif
                    </div>
                </section>
            @endif

            <section class="box section" id="reviews">
                <div class="sec-head"><h2>Verified customer feedback</h2></div>
                <div style="padding:0 16px 8px">
                    @forelse($reviews as $r)
                        <div class="review">
                            <div class="stars"><span class="stars-fill" style="--r: {{ (float) $r->rating * 20 }}%"></span></div>
                            @if($r->comment)<div style="margin:4px 0">{{ $r->comment }}</div>@endif
                            <div class="review-head">
                                <span>{{ $r->created_at?->format('d-m-Y') }} by {{ $r->user_name ?: 'Customer' }}@if($r->location) · {{ $r->location }}@endif</span>
                                <span style="color:var(--ok)">✓ Verified purchase</span>
                            </div>
                            @if($r->company_comment)<div class="reply"><strong>{{ $storeSettings['name'] }}:</strong> {{ $r->company_comment }}</div>@endif
                        </div>
                    @empty
                        <p class="muted" style="padding:12px 0">No reviews yet. Customers can rate products from their orders in the app.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Right column --}}
        <aside>
            <div class="box side-box">
                <h3>Delivery &amp; returns</h3>
                <div class="side-row"><span class="ico">🚚</span><div><strong>Door delivery</strong><br><span class="muted">GozakMart Delivery or a partner courier. You choose and see the fee at checkout.</span></div></div>
                <div class="side-row"><span class="ico">📍</span><div><strong>Live tracking</strong><br><span class="muted">Follow your order from packing to your door.</span></div></div>
                <div class="side-row"><span class="ico">🔒</span><div><strong>Secure payment</strong><br><span class="muted">Card, bank transfer or USSD via Paystack / OPay.</span></div></div>
                <div class="side-row"><span class="ico">↩️</span><div><strong>Returns</strong><br><span class="muted">Contact us from your order if something isn't right.</span></div></div>
            </div>
        </aside>
    </div>

    @if($related->isNotEmpty())
        <section class="box section">
            <div class="sec-head"><h2>You may also like</h2></div>
            <div class="rail">
                @foreach($related as $card)
                    @include('store.partials.product-card', ['card' => $card])
                @endforeach
            </div>
        </section>
    @endif
</div>

{{-- Sticky buy bar on phones --}}
<div class="sticky-buy">
    <div><strong data-price-sticky>₦{{ number_format($sale ?? $price) }}</strong></div>
    <button type="button" class="btn btn-primary" data-buy>Add to cart</button>
</div>

<div class="lightbox" data-lightbox hidden>
    <button class="lb-close" type="button" data-lb-close aria-label="Close">✕</button>
    <button class="lb-prev" type="button" data-lb-prev aria-label="Previous">‹</button>
    <div data-lb-stage></div>
    <button class="lb-next" type="button" data-lb-next aria-label="Next">›</button>
    <div class="lb-count" data-lb-count></div>
</div>
@endsection

@push('scripts')
@php
    $jsProduct = ['id' => $p['id'], 'price' => $price, 'sale' => $sale, 'stock' => (int) ($p['stock'] ?? 0), 'variable' => $isVariable];
    $jsVars = array_map(fn ($v) => ['id' => $v['id'], 'price' => (float) $v['price'], 'sale' => $v['sale_price'] ? (float) $v['sale_price'] : null, 'stock' => (int) $v['stock'], 'attrs' => (object) ($v['attributes'] ?? []), 'image' => $v['image'] ?? null], $vars);
    $jsAttrNames = array_values(array_map(fn ($a) => $a['name'], $attrs));
@endphp
<script>
(function () {
    const PRODUCT = {!! json_encode($jsProduct, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const VARS = {!! json_encode($jsVars, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const MEDIA = {!! json_encode($media->values(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const DEAL = @json($dealPct);
    const ATTR_NAMES = {!! json_encode($jsAttrNames, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    const esc = window.storeEsc || (s => s);
    const fmt = n => '₦' + Math.round(n).toLocaleString('en-NG');
    const root = document.getElementById('pdp');

    // ── Gallery ─────────────────────────────────────────────────────────────
    const main = root.querySelector('[data-gallery-main]');
    let cur = {{ $start }};
    function render(i) {
        cur = Math.max(0, Math.min(MEDIA.length - 1, i));
        const m = MEDIA[cur];
        if (!m) { main.innerHTML = '<div class="img-ph">GozakMart</div>'; return; }
        main.innerHTML = m.type === 'video'
            ? `<video src="${esc(m.url)}" ${m.poster_url ? `poster="${esc(m.poster_url)}"` : ''} controls playsinline preload="none"></video>`
            : `<img src="${esc(m.url)}" alt="${esc(@json($title))}">`;
        root.querySelectorAll('[data-thumb]').forEach(b => b.classList.toggle('on', +b.dataset.thumb === cur));
    }
    root.addEventListener('click', e => { const t = e.target.closest('[data-thumb]'); if (t) render(+t.dataset.thumb); });
    render(cur);

    // Lightbox (photos and videos, arrows/keys/swipe)
    const lb = document.querySelector('[data-lightbox]');
    const stage = lb.querySelector('[data-lb-stage]');
    function lbShow(i) {
        cur = (i + MEDIA.length) % MEDIA.length;
        const m = MEDIA[cur];
        stage.innerHTML = m.type === 'video'
            ? `<video src="${esc(m.url)}" controls autoplay playsinline></video>`
            : `<img src="${esc(m.url)}" alt="">`;
        lb.querySelector('[data-lb-count]').textContent = (cur + 1) + ' / ' + MEDIA.length;
    }
    main.addEventListener('click', e => {
        if (e.target.tagName === 'VIDEO' || !MEDIA.length) return;
        lb.hidden = false; document.body.style.overflow = 'hidden'; lbShow(cur);
    });
    main.addEventListener('keydown', e => { if (e.key === 'Enter') main.click(); });
    const lbClose = () => { lb.hidden = true; stage.innerHTML = ''; document.body.style.overflow = ''; render(cur); };
    lb.addEventListener('click', e => {
        if (e.target.closest('[data-lb-close]') || e.target === lb) lbClose();
        if (e.target.closest('[data-lb-prev]')) lbShow(cur - 1);
        if (e.target.closest('[data-lb-next]')) lbShow(cur + 1);
    });
    document.addEventListener('keydown', e => {
        if (lb.hidden) return;
        if (e.key === 'Escape') lbClose();
        if (e.key === 'ArrowLeft') lbShow(cur - 1);
        if (e.key === 'ArrowRight') lbShow(cur + 1);
    });
    let x0 = null;
    lb.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', e => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) lbShow(cur + (dx < 0 ? 1 : -1)); x0 = null; });

    // ── Options ─────────────────────────────────────────────────────────────
    const sel = {};
    const match = () => PRODUCT.variable
        ? VARS.find(v => ATTR_NAMES.every(n => String(v.attrs[n] ?? '') === String(sel[n] ?? ''))) || null
        : null;
    const available = (name, value) => VARS.some(v => String(v.attrs[name] ?? '') === value && v.stock > 0
        && ATTR_NAMES.every(n => n === name || sel[n] === undefined || String(v.attrs[n] ?? '') === sel[n]));

    function priceFor(base, sale) {
        let now = sale && sale < base ? sale : base;
        if (DEAL) { const d = Math.round(base * (1 - DEAL / 100) * 100) / 100; if (d > 0 && d < now) now = d; }
        return { now, was: now < base ? base : null };
    }

    function refresh() {
        const v = match();
        root.querySelectorAll('[data-opt]').forEach(box => {
            const name = box.dataset.opt;
            box.querySelector('[data-opt-label]').textContent = sel[name] || '';
            box.querySelectorAll('.opt-val').forEach(b => {
                b.classList.toggle('on', sel[name] === b.dataset.value);
                b.classList.toggle('na', !available(name, b.dataset.value));
            });
        });

        const base = v ? (v.price || PRODUCT.price) : PRODUCT.price;
        const pr = priceFor(base, v ? v.sale : PRODUCT.sale);
        root.querySelector('[data-price]').textContent = fmt(pr.now);
        document.querySelectorAll('[data-price-sticky]').forEach(el => el.textContent = fmt(pr.now));
        const wasWrap = root.querySelector('[data-was-wrap]');
        wasWrap.hidden = !pr.was;
        if (pr.was) {
            root.querySelector('[data-was]').textContent = fmt(pr.was);
            root.querySelector('[data-disc]').textContent = '-' + Math.round((pr.was - pr.now) / pr.was * 100) + '%';
        }

        const stock = PRODUCT.variable ? (v ? v.stock : null) : PRODUCT.stock;
        const line = root.querySelector('[data-stock-line]');
        const allChosen = ATTR_NAMES.every(n => sel[n] !== undefined);
        let label = '', cls = 'stock-line', canBuy = true;
        if (PRODUCT.variable && !allChosen) { label = 'Choose ' + ATTR_NAMES.filter(n => sel[n] === undefined).join(' and ').toLowerCase() + ' to see availability'; canBuy = false; }
        else if (PRODUCT.variable && !v) { label = 'This combination is not available'; cls += ' out'; canBuy = false; }
        else if (stock <= 0) { label = 'Out of stock'; cls += ' out'; canBuy = false; }
        else if (stock <= 5) { label = 'Only ' + stock + ' left. Order soon'; cls += ' low'; }
        else { label = '✓ In stock'; }
        line.className = cls; line.textContent = label;
        const qty = root.querySelector('[data-qty]');
        if (stock > 0) qty.max = Math.min(99, stock);
        document.querySelectorAll('[data-buy]').forEach(b => {
            b.disabled = !canBuy && !(PRODUCT.variable && !allChosen);
            b.textContent = !canBuy && (PRODUCT.variable && !allChosen) ? 'Choose options' : (canBuy ? 'Add to cart' : 'Out of stock');
        });
    }

    root.addEventListener('click', e => {
        const b = e.target.closest('.opt-val');
        if (!b) return;
        const name = b.closest('[data-opt]').dataset.opt;
        sel[name] = b.dataset.value;
        // Jump the gallery to this option's photo
        let i = MEDIA.findIndex(m => m.type === 'image' && m.attribute_name && m.attribute_name.toLowerCase() === name.toLowerCase() && m.attribute_value === b.dataset.value);
        const v = match();
        if (i < 0 && v && v.image) i = MEDIA.findIndex(m => m.url === v.image);
        if (i < 0 && v && v.image) { MEDIA.push({ type: 'image', url: v.image, thumb_url: v.image }); i = MEDIA.length - 1; }
        if (i >= 0) render(i);
        refresh();
    });

    // Start on the first in-stock combination (like the app)
    const first = VARS.find(v => v.stock > 0) || VARS[0];
    if (first) ATTR_NAMES.forEach(n => { if (first.attrs[n] !== undefined) sel[n] = String(first.attrs[n]); });
    refresh();

    // ── Add to cart ─────────────────────────────────────────────────────────
    document.addEventListener('click', async e => {
        const b = e.target.closest('[data-buy]');
        if (!b || b.disabled) return;
        if (PRODUCT.variable && !ATTR_NAMES.every(n => sel[n] !== undefined)) {
            root.querySelector('.opt')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            window.storeToast('Please choose ' + ATTR_NAMES.filter(n => sel[n] === undefined).join(' and ').toLowerCase());
            return;
        }
        const v = match();
        const qty = Math.max(1, parseInt(root.querySelector('[data-qty]').value, 10) || 1);
        await window.storeAddToCart(PRODUCT.id, v ? v.id : null, qty, b);
    });
})();
</script>
@endpush
