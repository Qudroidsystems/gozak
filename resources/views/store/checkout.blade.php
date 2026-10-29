@extends('store.layout')

@section('title', 'Checkout | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@section('content')
<div class="wrap">
    <form method="post" action="{{ route('store.checkout.place') }}" id="checkoutForm">
        @csrf
        <div class="two-col">
            <div class="box">
                {{-- 1. Address --}}
                <section class="step">
                    <h2><span class="n">1</span> Delivery address</h2>
                    @forelse($addresses as $a)
                        <label class="choice {{ $selected && $selected->id === $a->id ? 'on' : '' }}">
                            <input type="radio" name="address_id" value="{{ $a->id }}" @checked($selected && $selected->id === $a->id) data-address>
                            <div class="c-main">
                                <strong>{{ $a->name }}</strong> · {{ $a->phone_number }}
                                <small>{{ $a->street }}, {{ $a->lga ? $a->lga . ', ' : '' }}{{ $a->city }}, {{ $a->state }}</small>
                                @if($a->landmark)<small>Landmark: {{ $a->landmark }}</small>@endif
                                @if(!$a->latitude)<small style="color:var(--brand)">No map pin. Some delivery options need one. <a href="{{ route('store.account.addresses.edit', [$a->id, 'back' => 'checkout']) }}" style="text-decoration:underline">Add pin</a></small>@endif
                            </div>
                        </label>
                    @empty
                        <p class="muted">You don't have a delivery address yet.</p>
                    @endforelse
                    <a href="{{ route('store.account.addresses.create', ['back' => 'checkout']) }}" class="btn btn-outline btn-sm">+ Add a new address</a>
                    @error('address_id')<div class="form-err">{{ $message }}</div>@enderror
                </section>

                {{-- 2. Delivery --}}
                <section class="step">
                    <h2><span class="n">2</span> Delivery</h2>
                    <div id="deliveryNotice" class="flash flash-info" hidden></div>
                    <div id="deliveryOptions"><p class="muted">{{ $addresses->isEmpty() ? 'Add an address to see delivery options.' : 'Loading delivery options…' }}</p></div>
                    <div id="feePayer" hidden style="margin-top:8px"></div>
                    <input type="hidden" name="delivery_option_id" id="deliveryOptionId">
                    <input type="hidden" name="delivery_fee_payer" id="deliveryFeePayer" value="now">
                    <p class="muted" style="font-size:12px;margin-top:6px">Your items:
                        @foreach($cart['lines'] as $l){{ $l['quantity'] }}× {{ \Illuminate\Support\Str::limit($l['title'], 30) }}@if(!$loop->last), @endif @endforeach
                        · <a href="{{ route('store.cart') }}" style="color:var(--brand)">Edit cart</a>
                    </p>
                </section>

                {{-- 3. Payment --}}
                <section class="step">
                    <h2><span class="n">3</span> Payment method</h2>
                    @forelse($gateways as $g)
                        <label class="choice {{ ($defaultGw ?? $gateways[0]['key']) === $g['key'] ? 'on' : '' }}">
                            <input type="radio" name="payment_method" value="{{ $g['key'] }}" @checked(($defaultGw ?? $gateways[0]['key']) === $g['key'])>
                            <div class="c-main">
                                <strong>{{ $g['label'] ?? $g['name'] }}</strong>
                                <small>{{ $g['key'] === 'opay' ? 'OPay wallet, card or bank transfer' : 'Debit card, bank transfer or USSD' }}. You'll be taken to a secure payment page.</small>
                            </div>
                        </label>
                    @empty
                        <div class="flash flash-err">Online payment isn't available right now. Please try again later or use the app.</div>
                    @endforelse
                </section>
            </div>

            <aside class="box summary">
                <h2>Order summary</h2>
                <div class="sum-row"><span>Items ({{ $cart['count'] }})</span><span>₦{{ number_format($cart['subtotal'], 2) }}</span></div>
                <div class="sum-row"><span>Delivery</span><span id="sumDelivery">Choose an option</span></div>
                @if($cart['tax_enabled'])<div class="sum-row"><span>Tax ({{ rtrim(rtrim(number_format($cart['tax_rate'], 2), '0'), '.') }}%)</span><span>₦{{ number_format($cart['tax'], 2) }}</span></div>@endif
                <div class="sum-row total"><span>Total</span><span id="sumTotal">₦{{ number_format($cart['subtotal'] + $cart['tax'], 2) }}</span></div>
                <p class="note" id="payRiderNote" hidden></p>
                <button type="submit" class="btn btn-primary btn-block btn-lg" id="placeBtn" style="margin-top:12px" @disabled($addresses->isEmpty() || empty($gateways))>Confirm order</button>
                <p class="note">By placing your order you agree to our <a href="{{ route('legal.terms') }}" style="color:var(--brand)">terms</a>. The final total is confirmed on the payment page.</p>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const ENDPOINT = @json(route('store.checkout.delivery-options'));
    const SUBTOTAL = {{ (float) $cart['subtotal'] }};
    const TAX = {{ (float) $cart['tax'] }};
    const esc = window.storeEsc;
    const fmt = n => '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const box = document.getElementById('deliveryOptions');
    const notice = document.getElementById('deliveryNotice');
    const payerBox = document.getElementById('feePayer');
    const optInput = document.getElementById('deliveryOptionId');
    const payerInput = document.getElementById('deliveryFeePayer');
    let options = [], labels = {}, chosen = null;

    // Highlight chosen radio rows
    document.addEventListener('change', e => {
        if (e.target.type === 'radio' && e.target.closest('.choice')) {
            document.querySelectorAll(`input[name="${e.target.name}"]`).forEach(r => r.closest('.choice')?.classList.toggle('on', r.checked));
        }
        if (e.target.matches('[data-address]')) load(e.target.value);
        if (e.target.name === 'delivery_choice') choose(e.target.value);
        if (e.target.name === 'fee_payer_choice') { payerInput.value = e.target.value; totals(); }
    });

    async function load(addressId) {
        chosen = null; optInput.value = ''; payerInput.value = 'now'; payerBox.hidden = true; totals();
        box.innerHTML = '<p class="muted">Loading delivery options…</p>';
        notice.hidden = true;
        try {
            const res = await fetch(ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.STORE.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ address_id: addressId }),
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.message || 'Delivery options are not available right now.');
            const d = data.data || {};
            options = d.options || [];
            labels = d.fee_payer_labels || {};
            if (d.notice) { notice.textContent = d.notice; notice.hidden = false; }
            render(d.marketplace ? d.marketplace.name : null);
        } catch (err) {
            box.innerHTML = `<div class="flash flash-err">${esc(err.message)} <a href="#" data-retry style="text-decoration:underline">Try again</a></div>`;
        }
    }
    box.addEventListener('click', e => { if (e.target.closest('[data-retry]')) { e.preventDefault(); const r = document.querySelector('[data-address]:checked'); if (r) load(r.value); } });

    function render(marketplace) {
        if (!options.length) { box.innerHTML = '<p class="muted">No delivery options for this address yet. Try another address or contact us.</p>'; return; }
        const own = options.find(o => o.type !== 'courier');
        const ownName = own ? own.name : 'GozakMart Delivery';
        const partners = options.some(o => o.type === 'courier' && o.provider && o.provider !== ownName);
        let html = marketplace ? `<p class="muted" style="font-size:12px;margin:0 0 8px">Couriers by ${esc(ownName)}${partners ? ' and partners' : ''} · provided on ${esc(marketplace)}</p>` : '';
        html += options.map(o => {
            const can = o.priced !== false;
            const tags = (o.tags || []).map(t => `<span class="tag">${esc(t)}</span>`).join('');
            return `<label class="choice" ${can ? '' : 'style="opacity:.6"'}>
                <input type="radio" name="delivery_choice" value="${esc(o.id)}" ${can ? '' : 'disabled'}>
                <div class="c-main"><strong>${esc(o.name)}</strong>${tags}
                    <small>${esc([o.service, o.eta].filter(Boolean).join(' · '))}${o.rating ? ' · ★ ' + Number(o.rating).toFixed(1) : ''}</small>
                    ${o.needs_pin ? '<small>Needs a map pin on your address</small>' : ''}
                    ${can ? '' : '<small style="color:var(--brand)">Add a map pin to your address to see this price.</small>'}
                </div>
                <div class="c-price">${can ? (Number(o.price) > 0 ? fmt(o.price) : 'Free') : '—'}</div>
            </label>`;
        }).join('');
        box.innerHTML = html;
        const cheapest = options.find(o => o.priced !== false);
        if (cheapest) { const r = box.querySelector(`input[value="${CSS.escape(String(cheapest.id))}"]`); if (r) { r.checked = true; r.closest('.choice').classList.add('on'); choose(cheapest.id); } }
    }

    function choose(id) {
        chosen = options.find(o => String(o.id) === String(id)) || null;
        optInput.value = chosen ? chosen.id : '';
        const payers = chosen && chosen.fee_payers ? chosen.fee_payers : ['now'];
        payerInput.value = payers.includes('now') ? 'now' : payers[0];
        if (payers.length > 1) {
            payerBox.hidden = false;
            payerBox.innerHTML = '<strong style="font-size:13px">Pay for delivery</strong>' + payers.map(p =>
                `<label class="check" style="margin-top:6px"><input type="radio" name="fee_payer_choice" value="${esc(p)}" ${p === payerInput.value ? 'checked' : ''}> ${esc(labels[p] || (p === 'now' ? 'Now, with my order' : 'Cash to the rider on delivery'))}</label>`).join('');
        } else {
            payerBox.hidden = true;
        }
        totals();
    }

    function totals() {
        const fee = chosen ? Number(chosen.price) || 0 : 0;
        const payNow = payerInput.value !== 'on_delivery';
        document.getElementById('sumDelivery').textContent = chosen ? (fee > 0 ? fmt(fee) + (payNow ? '' : ' (pay rider)') : 'Free') : 'Choose an option';
        document.getElementById('sumTotal').textContent = fmt(SUBTOTAL + TAX + (payNow ? fee : 0));
        const note = document.getElementById('payRiderNote');
        note.hidden = payNow || !fee;
        note.textContent = `Pay ${fmt(fee)} in cash to the rider when your order arrives.`;
    }

    document.getElementById('checkoutForm').addEventListener('submit', e => {
        if (options.length && !optInput.value) {
            e.preventDefault();
            window.storeToast('Please choose a delivery option.');
            return;
        }
        const b = document.getElementById('placeBtn');
        b.disabled = true; b.textContent = 'Placing your order…';
    });

    const first = document.querySelector('[data-address]:checked');
    if (first) load(first.value);
})();
</script>
@endpush
