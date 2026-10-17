@extends('layouts.master')

@section('title', 'Logistics marketplace')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('dispatch._nav', ['title' => 'Logistics marketplace', 'subtitle' => 'Let customers pick a courier from the logistics marketplace at checkout — next to GozakMart Delivery.', 'back' => route('dispatch.index'), 'backLabel' => 'Dispatch board'])

    @php
        $live = $s->isLive();
        $pickup = $branches->firstWhere('id', $s->pickup_stock_location_id) ?? $branches->firstWhere('id', \App\Models\StockLocation::getDefault()?->id);
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-md-3"><x-cb.stat label="At checkout" :value="$live ? 'Showing couriers' : 'Hidden'" icon="ri-shopping-cart-2-line" :accent="$live ? 'green' : 'amber'" :hint="$live ? ($s->marketplace_name ?? '') : ($s->enabled ? 'waiting for verification' : 'switched off')" /></div>
        <div class="col-md-3"><x-cb.stat label="Merchant status" :value="$s->merchant_status ? ucfirst($s->merchant_status) : 'Not connected'" icon="ri-shield-check-line" :accent="$s->merchant_status === 'verified' ? 'green' : 'rose'" :hint="$s->merchant_code ? 'Code ' . $s->merchant_code . ' · ' . ($s->environment ?? '') . ' key' : null" /></div>
        <div class="col-md-3"><x-cb.stat label="Courier deliveries" :value="$counts->sum()" icon="ri-truck-line" accent="sky" :hint="($counts['delivered'] ?? 0) . ' delivered · ' . (($counts['booked'] ?? 0) + ($counts['assigned'] ?? 0) + ($counts['picked_up'] ?? 0)) . ' on the way'" /></div>
        <div class="col-md-3"><x-cb.stat label="Need attention" :value="($counts['failed'] ?? 0) + ($counts['delivery_failed'] ?? 0)" icon="ri-error-warning-line" :accent="(($counts['failed'] ?? 0) + ($counts['delivery_failed'] ?? 0)) ? 'rose' : 'green'" hint="booking or delivery failed" /></div>
    </div>

    @if($s->last_error)<div class="alert alert-warning small"><i class="ri-error-warning-line"></i> Last connection problem: {{ $s->last_error }}</div>@endif
    @if($s->enabled && !$pickup?->latitude)<div class="alert alert-danger small"><i class="ri-map-pin-line"></i> The pickup branch has no latitude/longitude, so couriers can't be priced. Set it under Inventory › Stock locations.</div>@endif

    <div class="row g-3">
        <div class="col-xl-7">
            <form method="POST" action="{{ route('dispatch.logistics.save') }}">
                @csrf @method('PUT')
                <x-cb.card title="Connection" icon="ri-plug-line">
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="enabled" value="0">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="lgOn" @checked($s->enabled)>
                        <label class="form-check-label" for="lgOn"><b>Show marketplace couriers at checkout</b>
                            <div class="small text-muted">Only once the marketplace reports GozakMart as a <b>verified</b> merchant.</div></label>
                    </div>
                    <label class="form-label small mb-1">Partner API address</label>
                    <input type="url" name="base_url" class="form-control form-control-sm mb-2" placeholder="https://your-logistics-domain/api/v1/partner" value="{{ $s->base_url }}">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">API key {{ $s->maskedKey() ? '(' . $s->maskedKey() . ')' : '' }}</label>
                            <input type="password" name="api_key" class="form-control form-control-sm" autocomplete="off" placeholder="{{ $s->api_key ? 'leave blank to keep' : 'lgk_live_…' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Webhook signing secret</label>
                            <input type="password" name="webhook_secret" class="form-control form-control-sm" autocomplete="off" placeholder="{{ $s->webhook_secret ? 'leave blank to keep' : 'from the marketplace webhook page' }}">
                        </div>
                    </div>
                    <div class="small text-muted mt-2">Get both from the marketplace's <b>Merchant › API</b> page. Add this webhook address there (all delivery events):</div>
                    <div class="input-group input-group-sm mt-1">
                        <input class="form-control font-monospace" readonly value="{{ $webhookUrl }}" id="lgHook">
                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('lgHook').value)">Copy</button>
                    </div>
                </x-cb.card>

                <x-cb.card title="Checkout & booking" icon="ri-shopping-bag-3-line">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Couriers collect from</label>
                            <select name="pickup_stock_location_id" class="form-select form-select-sm">
                                <option value="">Default branch</option>
                                @foreach($branches as $b)<option value="{{ $b->id }}" @selected($s->pickup_stock_location_id == $b->id)>{{ $b->name }}{{ $b->latitude ? '' : ' (no map position!)' }}</option>@endforeach
                            </select>
                            <div class="small text-muted">An order packed at another branch is collected from that branch.</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Default weight (g)</label>
                            <input type="number" name="default_weight_g" class="form-control form-control-sm" min="100" value="{{ $s->default_weight_g ?? 2000 }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Absorb price rise up to (%)</label>
                            <input type="number" name="max_price_increase_percent" class="form-control form-control-sm" min="0" max="50" value="{{ $s->max_price_increase_percent ?? 10 }}">
                        </div>
                    </div>
                    <div class="small text-muted mb-3">Prices shown at checkout last a short time; the courier is booked when packing is verified. If the provider's price went up by more than this, booking stops and you choose what to do.</div>

                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="allow_receiver_pays" value="0">
                        <input class="form-check-input" type="checkbox" name="allow_receiver_pays" value="1" id="lgRecv" @checked($s->allow_receiver_pays ?? true)>
                        <label class="form-check-label" for="lgRecv"><b>Let customers pay the courier's rider on delivery</b><div class="small text-muted">Otherwise the delivery fee is always added to the order.</div></label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="own_delivery_enabled" value="0">
                        <input class="form-check-input" type="checkbox" name="own_delivery_enabled" value="1" id="lgOwn" @checked($s->own_delivery_enabled ?? true)>
                        <label class="form-check-label" for="lgOwn"><b>Also offer GozakMart's own riders</b></label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="own_via_marketplace" value="0">
                        <input class="form-check-input" type="checkbox" name="own_via_marketplace" value="1" id="lgOwnVia" @checked($s->own_via_marketplace ?? false) @disabled(!$s->own_provider_name && !($s->own_via_marketplace ?? false))>
                        <label class="form-check-label" for="lgOwnVia"><b>Run "{{ $s->own_delivery_label ?? 'GozakMart Delivery' }}" through our delivery team on {{ $s->marketplace_name ?: 'the marketplace' }}</b>
                            <div class="small text-muted">
                                @if($s->own_provider_name)
                                    Orders are booked to <b>{{ $s->own_provider_name }}</b> when packing is verified. Your team assigns the rider in the logistics app, and the customer follows the rider live in the GozakMart app. Customers must pin their address on the map. The marketplace's commission applies.
                                @else
                                    First link your delivery team: in the logistics app, open your merchant API page › <i>Your own delivery team</i>, choose your provider account, then come back and Save here.
                                @endif
                            </div>
                        </label>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-label small mb-1">Name at checkout</label><input name="own_delivery_label" class="form-control form-control-sm" maxlength="80" value="{{ $s->own_delivery_label ?? 'GozakMart Delivery' }}"></div>
                        <div class="col-md-6"><label class="form-label small mb-1">Delivery time shown</label><input name="own_delivery_eta" class="form-control form-control-sm" maxlength="80" value="{{ $s->own_delivery_eta ?? 'Same day' }}"></div>
                    </div>
                </x-cb.card>

                <div class="d-flex gap-2 mb-2">
                    <button class="btn btn-primary"><i class="ri-save-3-line"></i> Save</button>
                </div>
            </form>
            <form method="POST" action="{{ route('dispatch.logistics.test') }}" class="mb-4">
                @csrf <button class="btn btn-outline-secondary" @disabled(!$s->api_key)><i class="ri-pulse-line"></i> Test connection</button>
            </form>
        </div>

        <div class="col-xl-5">
            <x-cb.card title="How it works" icon="ri-route-line">
                <ol class="small mb-0 ps-3">
                    <li class="mb-1">At checkout the app asks for delivery options. Customers see <b>{{ $s->own_delivery_label ?? 'GozakMart Delivery' }}</b> and every courier on <b>{{ $s->marketplace_name ?? 'the marketplace' }}</b> with its price, speed and rating.</li>
                    <li class="mb-1">They choose to pay delivery <b>now</b> (added to the order — GozakMart pays the marketplace) or <b>to the rider</b> in cash.</li>
                    <li class="mb-1">When packing is verified, GozakMart books that courier. The order leaves your own dispatch board.</li>
                    <li class="mb-1">Picked up → order <b>shipped</b>; delivered → order <b>delivered</b> (webhooks, plus a check every 10 minutes).</li>
                    <li>The marketplace takes its commission from the provider's payout, not from the customer's price.</li>
                </ol>
            </x-cb.card>

            <x-cb.card title="Recent courier orders" icon="ri-truck-line" :flush="true">
                <div class="list-group list-group-flush small">
                    @forelse($recent as $r)
                        <a href="{{ route('adminorders.show', $r->order_id) }}#packing" class="list-group-item list-group-item-action d-flex justify-content-between">
                            <span><b>{{ $r->order?->ref }}</b> · {{ $r->provider_name }}<span class="text-muted"> · ₦{{ number_format($r->price ?? $r->quoted_price) }}{{ $r->fee_payer === 'receiver' ? ' (paid to rider)' : '' }}</span></span>
                            <span class="badge bg-{{ $r->badge() }}">{{ $r->label() }}</span>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">No courier orders yet.</div>
                    @endforelse
                </div>
            </x-cb.card>
        </div>
    </div>

</div>
</div>
</div>
@endsection
