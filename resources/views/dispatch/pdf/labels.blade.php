{{--
    Thermal parcel labels — one label per page.
    standard: 100 × 150 mm (4 × 6 in)   ·   compact: 100 × 75 mm
--}}
@php use App\Support\DeliveryInfo as D; use App\Support\Barcodes; use Illuminate\Support\Str; @endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Parcel labels</title>
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #000; }
    /* No explicit width: the block fills the label. Height = label minus padding so it never spills onto a 2nd page. */
    .label { height: 141mm; padding: 4mm; overflow: hidden; page-break-after: always; }
    .label.compact { height: 69mm; padding: 2.5mm 3mm; }
    .label.last { page-break-after: auto; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 0; }
    .store { font-size: 8pt; font-weight: bold; text-transform: uppercase; letter-spacing: .5pt; }
    .muted { font-size: 6.5pt; color: #222; }
    .route { background: #000; color: #fff; text-align: center; padding: 1.2mm 2mm; }
    .route .code { font-size: 18pt; font-weight: bold; line-height: 1; }
    .route .stop { font-size: 7pt; margin-top: .6mm; }
    .rule { border-top: 0.6mm solid #000; margin: 2.5mm 0; }
    .order { font-size: 15pt; font-weight: bold; line-height: 1.1; }
    .pcount { font-size: 24pt; font-weight: bold; line-height: 1; text-align: right; }
    .pcount small { display: block; font-size: 7pt; font-weight: normal; }
    .to-h { font-size: 6.5pt; text-transform: uppercase; letter-spacing: .5pt; }
    .name { font-size: 13pt; font-weight: bold; line-height: 1.15; }
    .area { font-size: 14pt; font-weight: bold; text-transform: uppercase; line-height: 1.15; margin-top: .8mm; }
    .addr { font-size: 9pt; line-height: 1.25; margin-top: .6mm; }
    .tags { margin-top: 2mm; }
    .tag { display: inline-block; border: 0.5mm solid #000; padding: .6mm 1.6mm; margin: 0 1mm 1mm 0; font-size: 8.5pt; font-weight: bold; }
    .tag.inv { background: #000; color: #fff; }
    .bc img { width: 100%; height: 17mm; }
    .bc-text { font-family: 'DejaVu Sans Mono', monospace; font-size: 10pt; font-weight: bold; text-align: center; letter-spacing: 1pt; }
    .qr img { width: 27mm; height: 27mm; }
    .foot { margin-top: 3mm; font-size: 6.5pt; border-top: 0.3mm solid #000; padding-top: 1mm; }

    /* compact 100 × 75 */
    .compact .route .code { font-size: 13pt; }
    .compact .rule { margin: 1.5mm 0; }
    .compact .order { font-size: 11pt; }
    .compact .name { font-size: 9.5pt; }
    .compact .area { font-size: 10pt; margin-top: .4mm; }
    .compact .tags { margin-top: 1mm; }
    .compact .tag { font-size: 5.5pt; padding: .3mm 1mm; margin: 0 .8mm .8mm 0; }
    .compact .bc img { height: 9mm; }
    .compact .bc-text { font-size: 7.5pt; }
    .compact .qr img { width: 18mm; height: 18mm; }
</style>
</head>
<body>
@foreach($parcels as $p)
    @php
        $o      = $p->order;
        $total  = $o->package_count ?: $o->parcels->count();
        $batch  = $o->dispatchBatch;
        $route  = $batch?->route_code ?? ($o->deliveryZone ? $o->deliveryZone->code : 'UNASSIGNED');
        $stop   = $o->stop_sequence ? 'STOP ' . $o->stop_sequence : ($batch ? $batch->code : 'ROUTE');
        $tags   = $p->handlingLabels();
        $qr     = Barcodes::qr("GOZAK|{$p->code}|{$o->ref}|{$p->sequence}/{$total}", 4);
        $bar    = Barcodes::code128($p->code, 2, 60);
    @endphp

    @if($compact)
    <div class="label compact {{ $loop->last ? 'last' : '' }}">
        <table>
            <tr>
                <td>
                    <div class="store">{{ $store['name'] }}</div>
                    <div class="order">{{ $o->ref }} &nbsp;·&nbsp; {{ $p->sequence }} of {{ $total }}</div>
                </td>
                <td style="width: 27mm;">
                    <div class="route"><div class="code">{{ $route }}</div><div class="stop">{{ $stop }}</div></div>
                </td>
            </tr>
        </table>
        <div class="rule"></div>
        <table>
            <tr>
                <td style="padding-right: 2mm;">
                    <div class="name">{{ Str::limit(D::recipient($o), 28) }}</div>
                    <div class="area">{{ Str::limit(D::area($o), 30) }}</div>
                    @if($tags)
                        <div class="tags">@foreach(array_slice($tags, 0, 3) as $t)<span class="tag {{ $loop->first ? 'inv' : '' }}">{{ $t }}</span>@endforeach</div>
                    @endif
                </td>
                <td class="qr" style="width: 19mm; text-align: right;">@if($qr)<img src="{{ $qr }}">@endif</td>
            </tr>
        </table>
        <div class="bc" style="margin-top: 1.5mm;">
            @if($bar)<img src="{{ $bar }}">@endif
            <div class="bc-text">{{ $p->code }}</div>
        </div>
    </div>
    @else
    <div class="label {{ $loop->last ? 'last' : '' }}">
        <table>
            <tr>
                <td>
                    <div class="store">{{ $store['name'] }}</div>
                    <div class="muted">{{ $o->branch?->name ?? 'Home delivery' }}@if($o->delivery_date) · Due {{ $o->delivery_date->format('D j M') }}@endif</div>
                </td>
                <td style="width: 36mm;">
                    <div class="route"><div class="code">{{ $route }}</div><div class="stop">{{ $stop }}</div></div>
                </td>
            </tr>
        </table>

        <div class="rule"></div>

        <table>
            <tr>
                <td>
                    <div class="to-h">Order</div>
                    <div class="order">{{ $o->ref }}</div>
                    <div class="muted">{{ $p->typeLabel() }}@if($p->weight_kg) · {{ rtrim(rtrim(number_format($p->weight_kg, 2), '0'), '.') }} kg @endif</div>
                </td>
                <td class="pcount" style="width: 38mm;"><small>PARCEL</small>{{ $p->sequence }} of {{ $total }}</td>
            </tr>
        </table>

        <div class="rule"></div>

        <div class="to-h">Deliver to</div>
        <div class="name">{{ Str::limit(D::recipient($o), 34) }}</div>
        <div class="area">{{ Str::limit(D::area($o), 40) }}</div>
        <div class="addr">{{ D::shortAddress($o, 80) }}@if(D::landmark($o))<br>Near: {{ Str::limit(D::landmark($o), 50) }}@endif</div>

        @if($tags)
            <div class="tags">@foreach($tags as $t)<span class="tag {{ $loop->first ? 'inv' : '' }}">{{ $t }}</span>@endforeach</div>
        @endif

        <div class="rule"></div>

        <table>
            <tr>
                <td class="bc" style="padding-right: 2mm;">
                    @if($bar)<img src="{{ $bar }}">@endif
                    <div class="bc-text">{{ $p->code }}</div>
                </td>
                <td class="qr" style="width: 28mm; text-align: right;">@if($qr)<img src="{{ $qr }}">@endif</td>
            </tr>
        </table>

        <div class="foot">
            <table>
                <tr>
                    <td>{{ $batch ? 'Batch ' . $batch->code : 'Not yet batched' }}</td>
                    <td style="text-align:right;">Printed {{ now()->format('j M Y H:i') }}</td>
                </tr>
            </table>
        </div>
    </div>
    @endif
@endforeach
</body>
</html>
