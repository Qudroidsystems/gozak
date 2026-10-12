{{-- A4 landscape dispatch manifest — the rider's run sheet and the store's hand-over record. --}}
@php
    use App\Support\DeliveryInfo as D;
    use App\Support\Barcodes;
    $orders      = $batch->orders;
    $parcelCount = $orders->sum(fn ($o) => $o->parcels->count());
    $collectSum  = $orders->sum(fn ($o) => D::amountToCollect($o));
    $bar         = Barcodes::code128($batch->code, 2, 46);
    $statusText  = [
        'assigned'         => 'Assigned',
        'out_for_delivery' => 'Out for delivery',
        'delivered'        => 'Delivered',
        'failed'           => 'Failed',
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Manifest {{ $batch->code }}</title>
<style>
    @page { margin: 20px 24px 34px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #111; margin: 0; }
    table { width: 100%; border-collapse: collapse; }
    td, th { vertical-align: top; }
    .store { font-size: 10px; font-weight: bold; text-transform: uppercase; }
    .title { font-size: 19px; font-weight: bold; letter-spacing: 1px; }
    .muted { color: #555; }
    .route { background: #111; color: #fff; text-align: center; padding: 6px 12px; }
    .route .c { font-size: 22px; font-weight: bold; line-height: 1; }
    .route .s { font-size: 9px; margin-top: 2px; }
    .bc { text-align: right; }
    .bc img { height: 34px; width: 200px; }
    .boxes td { border: 1px solid #999; padding: 6px 8px; width: 25%; }
    .k { font-size: 8px; text-transform: uppercase; color: #555; letter-spacing: .4px; }
    .v { font-size: 11px; font-weight: bold; }
    .big { font-size: 16px; font-weight: bold; }
    .stops th { background: #111; color: #fff; padding: 5px 5px; font-size: 8.5px; text-align: left; text-transform: uppercase; letter-spacing: .3px; }
    .stops td { border-bottom: 1px solid #999; border-left: 1px solid #ddd; padding: 5px; }
    .stops tr:nth-child(even) td { background: #f5f5f5; }
    .stopno { font-size: 16px; font-weight: bold; text-align: center; width: 30px; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }
    .pc { white-space: nowrap; font-size: 8.5px; }
    .tag { display: inline-block; border: 1px solid #111; padding: 0 3px; font-size: 7px; font-weight: bold; }
    .money { text-align: right; white-space: nowrap; font-weight: bold; }
    .sig { height: 26px; }
    .confirm td { width: 33.3%; padding: 0 12px 0 0; }
    .line { border-bottom: 1px solid #111; height: 24px; }
    .footer { position: fixed; bottom: -22px; left: 0; right: 0; font-size: 8px; color: #555; }
</style>
</head>
<body>

<div class="footer">
    <table><tr>
        <td>{{ $store['name'] }} · Manifest {{ $batch->code }} · Route {{ $batch->route_code }}</td>
        <td style="text-align:right;">Printed {{ now()->format('j M Y, g:i A') }}</td>
    </tr></table>
</div>

<table>
    <tr>
        <td>
            <div class="store">{{ $store['name'] }}@if($batch->branch) · {{ $batch->branch->name }}@endif</div>
            <div class="title">DISPATCH MANIFEST</div>
            <div class="muted">{{ $batch->statusLabel() }} · created {{ $batch->created_at->format('D j M Y, g:i A') }}@if($batch->creator) by {{ $batch->creator->full_name }}@endif</div>
        </td>
        <td style="width: 120px; padding: 0 12px;">
            <div class="route"><div class="c">{{ $batch->route_code }}</div><div class="s">{{ $batch->zone?->name ?? 'Mixed areas' }}</div></div>
        </td>
        <td class="bc" style="width: 210px;">
            @if($bar)<img src="{{ $bar }}"><br>@endif
            <span class="mono" style="font-size: 12px; font-weight: bold;">{{ $batch->code }}</span>
        </td>
    </tr>
</table>

<table class="boxes" style="margin-top: 8px;">
    <tr>
        <td>
            <div class="k">Rider</div>
            <div class="v">{{ $batch->rider?->name ?? 'NOT ASSIGNED' }}</div>
            <div>{{ $batch->rider?->phone }}</div>
        </td>
        <td>
            <div class="k">Vehicle</div>
            <div class="v">{{ $batch->rider ? $batch->rider->vehicleLabel() : '—' }}</div>
        </td>
        <td>
            <div class="k">Departure</div>
            <div class="v">
                @if($batch->dispatched_at){{ $batch->dispatched_at->format('D j M, g:i A') }}
                @elseif($batch->planned_departure_at)Planned {{ $batch->planned_departure_at->format('D j M, g:i A') }}
                @else ____________ @endif
            </div>
            @if($batch->dispatcher)<div>Released by {{ $batch->dispatcher->full_name }}</div>@endif
        </td>
        <td>
            <table>
                <tr>
                    <td style="border:0;padding:0;"><div class="k">Stops</div><div class="big">{{ $orders->count() }}</div></td>
                    <td style="border:0;padding:0;"><div class="k">Parcels</div><div class="big">{{ $parcelCount }}</div></td>
                    <td style="border:0;padding:0;"><div class="k">To collect</div><div class="big">₦{{ number_format($collectSum) }}</div></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
@if($batch->notes)<div style="margin-top:6px;"><strong>Notes:</strong> {{ $batch->notes }}</div>@endif

<table class="stops" style="margin-top: 10px;">
    <thead>
        <tr>
            <th>Stop</th>
            <th>Order</th>
            <th>Customer / contact</th>
            <th>Area &amp; address</th>
            <th>Parcels (tick on hand-over)</th>
            <th style="text-align:right;">Collect</th>
            <th>Status</th>
            <th style="width: 120px;">Received by · time</th>
        </tr>
    </thead>
    <tbody>
    @forelse($orders as $o)
        @php $amt = D::amountToCollect($o); @endphp
        <tr>
            <td class="stopno">{{ $o->stop_sequence }}</td>
            <td>
                <strong>{{ $o->ref }}</strong>
                @if($o->delivery_date)<br><span class="muted">Due {{ $o->delivery_date->format('j M') }}</span>@endif
            </td>
            <td>
                <strong>{{ D::recipient($o) }}</strong><br>
                {{ D::phone($o) ?? '—' }}@if(D::altPhone($o))<br>{{ D::altPhone($o) }}@endif
            </td>
            <td>
                <strong>{{ D::area($o) }}</strong><br>
                {{ \Illuminate\Support\Str::limit(D::shortAddress($o, 120), 120) }}
                @if(D::landmark($o))<br><span class="muted">Near {{ D::landmark($o) }}</span>@endif
            </td>
            <td class="pc">
                @foreach($o->parcels as $p)
                    {{ $p->status === 'delivered' ? '☑' : '☐' }} <span class="mono">{{ $p->code }}</span> ({{ $p->sequence }}/{{ $o->parcels->count() }})
                    @foreach($p->handlingLabels() as $t)<span class="tag">{{ $t }}</span>@endforeach<br>
                @endforeach
            </td>
            <td class="money">{{ $amt > 0 ? '₦' . number_format($amt, 2) : 'PAID' }}</td>
            <td>
                {{ $statusText[$o->dispatch_status] ?? ucfirst((string) $o->dispatch_status) }}
                @if($o->dispatch_status === 'failed' && $o->dispatch_note)<br><span class="muted">{{ $o->dispatch_note }}</span>@endif
            </td>
            <td class="sig"></td>
        </tr>
    @empty
        <tr><td colspan="8" style="text-align:center;">No stops on this batch.</td></tr>
    @endforelse
    </tbody>
</table>

<table class="confirm" style="margin-top: 18px; page-break-inside: avoid;">
    <tr>
        <td>
            <div class="k">Collection confirmation — rider</div>
            <div>I have received <strong>{{ $parcelCount }}</strong> parcel(s) for <strong>{{ $orders->count() }}</strong> stop(s), all labelled {{ $batch->route_code }}.</div>
            <div class="line"></div><div class="k">Rider signature &amp; time</div>
        </td>
        <td>
            <div class="k">Released by — dispatch supervisor</div>
            <div>Parcels counted and scanned onto the vehicle.</div>
            <div class="line"></div><div class="k">Supervisor signature &amp; time</div>
        </td>
        <td>
            <div class="k">Return check-in</div>
            <div>Parcels returned: ______ &nbsp; Cash handed in: ₦__________</div>
            <div class="line"></div><div class="k">Received by &amp; time</div>
        </td>
    </tr>
</table>

</body>
</html>
