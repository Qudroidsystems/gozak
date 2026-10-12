{{-- A4 packing slip: pick → pack → check before the parcels are sealed. --}}
@php
    use App\Support\DeliveryInfo as D;
    use App\Support\Barcodes;
    $variation = function ($v) {
        if (empty($v)) return '';
        if (is_string($v)) { $v = json_decode($v, true); }
        if (!is_array($v)) return '';
        return collect($v)->map(fn ($val, $k) => ucfirst((string) $k) . ': ' . (is_array($val) ? implode(', ', $val) : $val))->implode(' · ');
    };
    $unit = function ($item) {
        $u = $item->product?->primaryUnit?->first();
        return $u ? ($u->short_name ?: $u->abbreviation ?: $u->name) : 'pcs';
    };
    $parcels  = $order->parcels;
    $expected = $order->package_count ?: $parcels->count();
    $statusText = [
        'unpacked' => 'NOT PACKED',
        'packing'  => 'PACKING',
        'packed'   => 'PACKED — AWAITING CHECK',
        'verified' => 'VERIFIED',
    ][$order->packing_status ?? 'unpacked'] ?? strtoupper((string) $order->packing_status);
    $bar = Barcodes::code128($order->ref, 2, 50);
    $collect = D::amountToCollect($order);
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Packing slip {{ $order->ref }}</title>
<style>
    @page { margin: 22px 26px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #111; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }
    .head td { padding-bottom: 8px; }
    .title { font-size: 20px; font-weight: bold; letter-spacing: 1px; }
    .store { font-size: 11px; font-weight: bold; text-transform: uppercase; }
    .muted { color: #555; }
    .bc { text-align: right; }
    .bc img { height: 38px; width: 210px; }
    .bc .ref { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
    .status { display: inline-block; padding: 3px 8px; border: 1.5px solid #111; font-weight: bold; font-size: 10px; margin-top: 4px; }
    .status.ok { background: #111; color: #fff; }
    .box { border: 1px solid #999; padding: 8px 10px; }
    .grid td { padding: 0 6px 0 0; width: 33.3%; }
    .k { font-size: 8.5px; text-transform: uppercase; color: #555; letter-spacing: .4px; }
    .v { font-size: 11.5px; font-weight: bold; margin-bottom: 6px; }
    h3 { font-size: 11.5px; margin: 14px 0 6px; text-transform: uppercase; letter-spacing: .5px; }
    .items th { background: #eee; border: 1px solid #999; padding: 6px; font-size: 9.5px; text-align: left; }
    .items td { border: 1px solid #999; padding: 6px; }
    .items .n { text-align: center; white-space: nowrap; }
    .chk { width: 42px; text-align: center; font-size: 14px; }
    .short { color: #b00; font-weight: bold; }
    .sub { font-size: 9.5px; margin-top: 3px; padding: 2px 4px; background: #fff3cd; border: 1px dashed #c90; }
    .tag { display: inline-block; border: 1px solid #111; padding: 1px 4px; font-size: 8.5px; font-weight: bold; margin: 1px 2px 1px 0; }
    .notes { border: 1.5px solid #111; padding: 8px 10px; min-height: 34px; font-size: 11px; }
    .sign td { width: 33.3%; padding: 0 10px 0 0; }
    .line { border-bottom: 1px solid #111; height: 26px; }
    .blank { display: inline-block; width: 40px; border-bottom: 1px solid #111; }
    .collect { border: 2px solid #111; padding: 6px 10px; font-size: 12px; font-weight: bold; text-align: center; }
</style>
</head>
<body>

<table class="head">
    <tr>
        <td>
            <div class="store">{{ $store['name'] }}</div>
            <div class="title">PACKING SLIP</div>
            <div class="muted">Printed {{ now()->format('D j M Y, g:i A') }}@if($order->branch) · {{ $order->branch->name }}@endif</div>
            <div class="status {{ $order->packing_status === 'verified' ? 'ok' : '' }}">{{ $statusText }}</div>
        </td>
        <td class="bc">
            @if($bar)<img src="{{ $bar }}"><br>@endif
            <span class="ref">ORDER {{ $order->ref }}</span>
        </td>
    </tr>
</table>

<div class="box">
    <table class="grid">
        <tr>
            <td>
                <div class="k">Order time</div>
                <div class="v">{{ ($order->order_date ?? $order->created_at)->format('D j M Y, g:i A') }}</div>
                <div class="k">Promised delivery</div>
                <div class="v">{{ $order->delivery_date ? $order->delivery_date->format('D j M Y') : 'Not set' }}</div>
            </td>
            <td>
                <div class="k">Deliver to</div>
                <div class="v">{{ D::recipient($order) }}</div>
                <div>{{ D::fullAddress($order) }}</div>
                @if(D::landmark($order))<div class="muted">Landmark: {{ D::landmark($order) }}</div>@endif
                @if(D::phone($order))<div>Tel: {{ D::phone($order) }}@if(D::altPhone($order)) / {{ D::altPhone($order) }}@endif</div>@endif
            </td>
            <td>
                <div class="k">Zone / route</div>
                <div class="v">{{ $order->deliveryZone ? $order->deliveryZone->code . ' — ' . $order->deliveryZone->name : 'Unzoned' }}@if($order->dispatchBatch) · {{ $order->dispatchBatch->route_code }}@endif</div>
                <div class="k">Packages expected</div>
                <div class="v" style="font-size: 16px;">@if($expected){{ $expected }}@else<span class="blank">&nbsp;</span>@endif</div>
                @if($collect > 0)
                    <div class="collect">COLLECT ₦{{ number_format($collect, 2) }}</div>
                @else
                    <div class="k">Payment</div><div class="v">PAID</div>
                @endif
            </td>
        </tr>
    </table>
</div>

<h3>Items to pick ({{ $order->items->sum('quantity') }} units · {{ $order->items->count() }} lines)</h3>
<table class="items">
    <thead>
        <tr>
            <th style="width: 22px;">#</th>
            <th>Item</th>
            <th>SKU</th>
            <th class="n">Unit</th>
            <th class="n">Ordered</th>
            <th class="n">Picked</th>
            <th class="chk">Packed</th>
            <th class="chk">Checked</th>
        </tr>
    </thead>
    <tbody>
    @forelse($order->items as $item)
        @php $short = $item->picked_quantity !== null && $item->picked_quantity < $item->quantity; @endphp
        <tr>
            <td class="n">{{ $loop->iteration }}</td>
            <td>
                <strong>{{ $item->title ?? $item->product?->name ?? 'Item' }}</strong>
                @if($v = $variation($item->selected_variation))<br><span class="muted">{{ $v }}</span>@endif
                @if($item->substitution)<div class="sub"><strong>SUBSTITUTION:</strong> {{ $item->substitution }}</div>@endif
            </td>
            <td>{{ $item->sku ?: '—' }}</td>
            <td class="n">{{ $unit($item) }}</td>
            <td class="n"><strong>{{ $item->quantity }}</strong></td>
            <td class="n {{ $short ? 'short' : '' }}">@if($item->picked_quantity !== null){{ $item->picked_quantity }}@else<span class="blank">&nbsp;</span>@endif</td>
            <td class="chk">{{ $order->packed_at ? '☑' : '☐' }}</td>
            <td class="chk">{{ $order->packing_status === 'verified' ? '☑' : '☐' }}</td>
        </tr>
    @empty
        <tr><td colspan="8" style="text-align:center;">No items</td></tr>
    @endforelse
    </tbody>
</table>

<h3>Parcels</h3>
@if($parcels->count())
    <table class="items">
        <thead><tr><th style="width: 60px;">Parcel</th><th>Code</th><th>Type</th><th>Handling</th><th>Contents</th><th class="n">Weight</th><th class="chk">Sealed</th></tr></thead>
        <tbody>
        @foreach($parcels as $p)
            <tr>
                <td class="n"><strong>{{ $p->sequence }} of {{ $expected }}</strong></td>
                <td style="font-family: 'DejaVu Sans Mono', monospace; font-weight: bold;">{{ $p->code }}</td>
                <td>{{ $p->typeLabel() }}</td>
                <td>@forelse($p->handlingLabels() as $t)<span class="tag">{{ $t }}</span>@empty — @endforelse</td>
                <td>{{ $p->contents ?: '—' }}</td>
                <td class="n">{{ $p->weight_kg ? rtrim(rtrim(number_format($p->weight_kg, 2), '0'), '.') . ' kg' : '—' }}</td>
                <td class="chk">☐</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="muted">Parcels not recorded yet. Number of bags / cartons: <span class="blank">&nbsp;</span> &nbsp; Handling: ☐ Fragile ☐ Keep upright ☐ Perishable ☐ Frozen ☐ Liquids ☐ Eggs</div>
@endif

<h3>Special packing notes</h3>
<div class="notes">{{ $order->packing_notes ?: ' ' }}</div>

<h3>Sign-off</h3>
<table class="sign">
    <tr>
        <td>
            <div class="k">Packed by</div>
            <div class="v">{{ $order->packer?->full_name ?: ' ' }}</div>
            <div class="muted">{{ $order->packed_at?->format('j M Y, g:i A') }}</div>
            <div class="line"></div><div class="k">Signature</div>
        </td>
        <td>
            <div class="k">Checked by</div>
            <div class="v">{{ $order->checker?->full_name ?: ' ' }}</div>
            <div class="muted">{{ $order->checked_at?->format('j M Y, g:i A') }}</div>
            <div class="line"></div><div class="k">Signature</div>
        </td>
        <td>
            <div class="k">Handed to rider</div>
            <div class="v">{{ $order->dispatchBatch?->rider?->name ?: ' ' }}</div>
            <div class="muted">{{ $order->dispatchBatch?->dispatched_at?->format('j M Y, g:i A') }}</div>
            <div class="line"></div><div class="k">Signature</div>
        </td>
    </tr>
</table>

</body>
</html>
