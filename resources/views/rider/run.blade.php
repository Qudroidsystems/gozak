@extends('rider.layout')

@section('title', $run->route_code)

@section('content')
@php
    use App\Support\DeliveryInfo as D;
    $out     = $run->status === 'dispatched';
    $open    = $run->orders->where('dispatch_status', 'out_for_delivery');
    $nextId  = session('open_stop') ?: $open->sortBy('stop_sequence')->first()?->id;
    $toCollect = $open->sum(fn ($o) => D::amountToCollect($o));
    $wa = function (?string $phone) {
        $d = preg_replace('/\D/', '', (string) $phone);
        if (str_starts_with($d, '0')) $d = '234' . substr($d, 1);
        return $d ? 'https://wa.me/' . $d : null;
    };
    $reasons = ['Customer not available', 'Phone unreachable', 'Wrong / incomplete address', 'Customer refused the order', 'Could not pay on delivery', 'Rescheduled by customer', 'Parcel damaged', 'Other'];
@endphp

<a href="{{ route('rider.index') }}" class="rd-muted text-decoration-none"><i class="ri-arrow-left-line"></i> My runs</a>

<div class="rd-card mt-2">
    <div class="d-flex justify-content-between align-items-center">
        <span class="rd-route fs-4">{{ $run->route_code }}</span>
        <span class="badge bg-{{ $run->statusBadge() }} fs-6">{{ $out ? 'On the road' : $run->statusLabel() }}</span>
    </div>
    <div class="mt-2 fw-semibold">{{ $run->orders->where('dispatch_status', 'delivered')->count() }} of {{ $run->orders->count() }} delivered</div>
    @if($toCollect > 0)<div class="rd-muted">Still to collect: <b class="text-dark">₦{{ number_format($toCollect, 2) }}</b></div>@endif
    @if($run->notes)<div class="alert alert-info py-2 mt-2 mb-0 small"><i class="ri-information-line"></i> {{ $run->notes }}</div>@endif
    @unless($out)
        <div class="alert alert-warning py-2 mt-2 mb-0 small">This run hasn't been dispatched yet — wait for the supervisor to hand over the parcels.</div>
    @endunless
</div>

@foreach($run->orders as $o)
    @php
        $isOpen  = $o->dispatch_status === 'out_for_delivery';
        $due     = D::amountToCollect($o);
        $phone   = D::phone($o);
        $needsCode = $settings->require_delivery_code && $o->delivery_code;
        $mapUrl  = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(D::fullAddress($o));
    @endphp
    <div class="rd-card rd-stop {{ !$isOpen && $out ? 'done' : '' }} {{ $o->id === $nextId && $out ? 'next' : '' }}" id="stop-{{ $o->stop_sequence }}">
        <div class="d-flex gap-3 align-items-start">
            <div class="rd-stopno">{{ $o->stop_sequence }}</div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between gap-2">
                    <div class="fw-bold">{{ D::recipient($o) }}</div>
                    @if($o->dispatch_status === 'delivered')<span class="badge bg-success">Delivered</span>
                    @elseif($o->dispatch_status === 'failed')<span class="badge bg-danger">Not delivered</span>
                    @elseif($o->id === $nextId && $out)<span class="badge bg-primary">Next</span>@endif
                </div>
                <div class="fw-semibold text-uppercase small">{{ D::area($o) }}</div>
                <div class="small">{{ D::fullAddress($o) }}</div>
                @if(D::landmark($o))<div class="rd-muted">Near {{ D::landmark($o) }}</div>@endif
                <div class="rd-muted">Order {{ $o->ref }}@if($o->promisedAt()) · due {{ $o->promisedAt()->format('D g:i A') }}@endif</div>
            </div>
        </div>

        <div class="mt-2">
            @foreach($o->parcels as $p)
                <span class="rd-parcel">{{ $p->code }} · {{ $p->sequence }}/{{ $o->parcels->count() }}</span>@foreach($p->handlingLabels() as $t)<span class="rd-tag">{{ $t }}</span>@endforeach
            @endforeach
        </div>

        @if($o->dispatch_status === 'failed' && $o->dispatch_note)<div class="small text-danger mt-1">{{ $o->dispatch_note }}</div>@endif

        @if($isOpen && $out)
            @if($due > 0)<div class="rd-collect mt-2"><i class="ri-money-dollar-circle-line"></i> Collect ₦{{ number_format($due, 2) }}</div>@endif

            <div class="rd-actions mt-3">
                @if($phone)
                    <a href="tel:{{ $phone }}" class="btn btn-outline-dark js-call" data-url="{{ route('rider.call', [$run->id, $o->id]) }}"><i class="ri-phone-line"></i><br>Call</a>
                    <a href="{{ $wa($phone) }}" target="_blank" class="btn btn-outline-success js-call" data-url="{{ route('rider.call', [$run->id, $o->id]) }}"><i class="ri-whatsapp-line"></i><br>WhatsApp</a>
                @else
                    <span class="btn btn-outline-secondary disabled">No phone</span><span></span>
                @endif
                <a href="{{ $mapUrl }}" target="_blank" class="btn btn-outline-primary"><i class="ri-map-pin-line"></i><br>Map</a>
            </div>

            {{-- Deliver --}}
            <form method="POST" action="{{ route('rider.deliver', [$run->id, $o->id]) }}" enctype="multipart/form-data" class="mt-3 js-geo">
                @csrf
                <input type="hidden" name="lat"><input type="hidden" name="lng">
                @if($needsCode)
                    <label class="form-label small mb-1">Customer's delivery code</label>
                    <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="one-time-code"
                           class="form-control form-control-lg rd-code mb-1" placeholder="• • • •" value="{{ old('code') && session('open_stop') === $o->id ? old('code') : '' }}">
                    <div class="rd-muted mb-2">{{ $o->user_id ? 'The customer sees it in the GozakMart app (order tracking / notifications).' : 'Walk-in customer — dispatch can read the code to them by phone.' }}</div>
                @endif

                @if($due > 0)
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label small mb-1">Amount collected (₦)</label>
                            <input type="number" step="0.01" min="0" name="cash_collected" class="form-control" value="{{ number_format($due, 2, '.', '') }}">
                        </div>
                        <div class="col-5">
                            <label class="form-label small mb-1">Paid by</label>
                            <select name="cash_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="pos">POS</option>
                            </select>
                        </div>
                    </div>
                @endif

                @if(!$needsCode || $settings->allow_photo_fallback)
                    <details class="mb-2" {{ $needsCode ? '' : 'open' }}>
                        <summary class="small text-primary">{{ $needsCode ? 'Customer can\'t give the code? Take a photo instead' : 'Photo of the hand-over (optional)' }}</summary>
                        <div class="mt-2">
                            <input type="file" name="photo" accept="image/*" capture="environment" class="form-control mb-2">
                            <input type="text" name="receiver" maxlength="120" class="form-control" placeholder="Name of the person who received it">
                        </div>
                    </details>
                @endif

                <button class="btn btn-success btn-lg w-100"><i class="ri-checkbox-circle-line"></i> Delivered</button>
            </form>

            {{-- Fail --}}
            <details class="mt-2">
                <summary class="small text-danger">Couldn't deliver?</summary>
                <form method="POST" action="{{ route('rider.fail', [$run->id, $o->id]) }}" class="mt-2 js-geo">
                    @csrf
                    <input type="hidden" name="lat"><input type="hidden" name="lng">
                    <select name="reason" class="form-select mb-2" required>
                        @foreach($reasons as $r)<option>{{ $r }}</option>@endforeach
                    </select>
                    <input type="text" name="note" maxlength="300" class="form-control mb-2" placeholder="Extra detail (optional)">
                    <button class="btn btn-outline-danger w-100" onclick="return confirm('Mark this stop as NOT delivered?')">Mark as not delivered</button>
                </form>
            </details>
        @endif
    </div>
@endforeach
@endsection

@push('scripts')
<script>
(function () {
    // Location is attached to deliveries / failures when the phone allows it.
    if (navigator.geolocation && document.querySelector('.js-geo')) {
        navigator.geolocation.getCurrentPosition(p => {
            document.querySelectorAll('.js-geo').forEach(f => {
                f.querySelector('[name=lat]').value = p.coords.latitude.toFixed(7);
                f.querySelector('[name=lng]').value = p.coords.longitude.toFixed(7);
            });
        }, () => {}, { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 });
    }
    const token = document.querySelector('meta[name=csrf-token]').content;
    document.querySelectorAll('.js-call').forEach(a => a.addEventListener('click', () => {
        try { fetch(a.dataset.url, { method: 'POST', keepalive: true, headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } }); } catch (e) {}
    }));
    document.querySelectorAll('form').forEach(f => f.addEventListener('submit', () => {
        const b = f.querySelector('button:not([type=button])'); if (b) { setTimeout(() => { b.disabled = true; b.textContent = 'Saving…'; }, 0); }
    }));
    const next = document.querySelector('.rd-stop.next');
    if (next && !location.hash) next.scrollIntoView({ block: 'center' });
})();
</script>
@endpush
