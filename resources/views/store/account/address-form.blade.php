@extends('store.layout')

@section('title', ($address->exists ? 'Edit address' : 'Add address') . ' | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
@endpush

@php
    $states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT Abuja','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
    $v = fn ($k) => old($k, $address->{$k});
@endphp

@section('content')
<div class="wrap account">
    @include('store.account._nav')
    <section class="box">
        <div class="sec-head"><h2>{{ $address->exists ? 'Edit address' : 'Add a new address' }}</h2></div>
        <form method="post" class="form" action="{{ $address->exists ? route('store.account.addresses.update', $address->id) : route('store.account.addresses.store') }}" novalidate>
            @csrf
            @if($address->exists) @method('PUT') @endif
            @if($back)<input type="hidden" name="back" value="{{ $back }}">@endif

            <div class="row2">
                <div class="field">
                    <label for="name">Full name</label>
                    <input id="name" name="name" value="{{ $v('name') }}" required autocomplete="name">
                    @error('name')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="phone_number">Phone number</label>
                    <input id="phone_number" type="tel" name="phone_number" value="{{ $v('phone_number') }}" required placeholder="0803 123 4567" autocomplete="tel">
                    @error('phone_number')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="field">
                <label for="street">Address (house number and street)</label>
                <input id="street" name="street" value="{{ $v('street') }}" required autocomplete="street-address">
                @error('street')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="landmark">Landmark <span class="muted">(optional, helps the rider)</span></label>
                <input id="landmark" name="landmark" value="{{ $v('landmark') }}" placeholder="e.g. Opposite First Bank">
            </div>
            <div class="row2">
                <div class="field">
                    <label for="state">State</label>
                    <select id="state" name="state" required>
                        <option value="">Choose a state</option>
                        @foreach($states as $s)<option @selected($v('state') === $s)>{{ $s }}</option>@endforeach
                        @if($v('state') && !in_array($v('state'), $states, true))<option selected>{{ $v('state') }}</option>@endif
                    </select>
                    @error('state')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="city">City / town</label>
                    <input id="city" name="city" value="{{ $v('city') }}" required autocomplete="address-level2">
                    @error('city')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row2">
                <div class="field">
                    <label for="lga">LGA <span class="muted">(optional)</span></label>
                    <input id="lga" name="lga" value="{{ $v('lga') }}">
                </div>
                <div class="field">
                    <label for="alternate_phone">Another phone <span class="muted">(optional)</span></label>
                    <input id="alternate_phone" type="tel" name="alternate_phone" value="{{ $v('alternate_phone') }}">
                    @error('alternate_phone')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="field">
                <label>Pin the exact spot on the map <span class="muted">(recommended: couriers use it to price and find you)</span></label>
                <button type="button" class="btn btn-outline btn-sm" id="useLocation">📍 Use my current location</button>
                <div id="map" class="map-pick" role="application" aria-label="Map. Click to place the delivery pin."></div>
                <small class="muted" id="pinStatus">{{ $v('latitude') ? 'Pin saved. Drag it to adjust.' : 'Click the map to drop a pin, or use your current location.' }}</small>
                <input type="hidden" name="latitude" id="lat" value="{{ $v('latitude') }}">
                <input type="hidden" name="longitude" id="lng" value="{{ $v('longitude') }}">
            </div>

            <div class="field">
                <label class="check"><input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address->is_default))> Make this my default address</label>
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="btn btn-primary btn-lg">Save address</button>
                <a href="{{ $back === 'checkout' ? route('store.checkout') : route('store.account.addresses') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
    if (!window.L) return;
    const latEl = document.getElementById('lat'), lngEl = document.getElementById('lng'), status = document.getElementById('pinStatus');
    const has = latEl.value && lngEl.value;
    const start = has ? [parseFloat(latEl.value), parseFloat(lngEl.value)] : [6.5244, 3.3792]; // Lagos
    const map = L.map('map').setView(start, has ? 17 : 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
    let marker = null;
    function place(latlng, zoom) {
        if (!marker) {
            marker = L.marker(latlng, { draggable: true }).addTo(map);
            marker.on('dragend', () => place(marker.getLatLng()));
        } else {
            marker.setLatLng(latlng);
        }
        latEl.value = latlng.lat.toFixed(7);
        lngEl.value = latlng.lng.toFixed(7);
        status.textContent = 'Pin placed. Drag it to adjust.';
        if (zoom) map.setView(latlng, zoom);
    }
    if (has) place(L.latLng(start[0], start[1]));
    map.on('click', e => place(e.latlng));
    document.getElementById('useLocation').addEventListener('click', () => {
        if (!navigator.geolocation) { status.textContent = 'Your browser can’t share location. Click the map instead.'; return; }
        status.textContent = 'Finding you…';
        navigator.geolocation.getCurrentPosition(
            pos => place(L.latLng(pos.coords.latitude, pos.coords.longitude), 17),
            () => { status.textContent = 'Location not allowed. Click the map to drop the pin instead.'; },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    });
})();
</script>
@endpush
