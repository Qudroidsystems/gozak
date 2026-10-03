{{--
    Invoice PDF (DomPDF).
    DomPDF only understands simple CSS: no flex/grid, no web fonts, no emoji.
    DejaVu Sans is bundled with DomPDF and is the font that contains the ₦ sign.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->invoice_number ?? substr($order->id, 0, 8) }}</title>
    <style>
        @page { margin: 28px 30px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; line-height: 1.45; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        .header { background: #0f172a; color: #fff; padding: 22px 24px; border-radius: 10px; }
        .header h1 { font-size: 20px; margin: 0 0 2px; }
        .muted-light { color: #94a3b8; font-size: 10px; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #fbbf24; text-align: right; }
        .invoice-number { text-align: right; color: #e2e8f0; font-size: 12px; margin-top: 4px; }
        .logo { max-height: 50px; max-width: 140px; background: #fff; border-radius: 6px; padding: 4px; }
        .info td { vertical-align: top; padding-top: 14px; }
        .label { color: #94a3b8; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .paid { background: #dcfce7; color: #166534; }
        .unpaid { background: #fee2e2; color: #991b1b; }
        .section { margin-top: 18px; }
        .card { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; }
        .card h3 { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin: 0 0 6px; }
        .card p { margin: 0 0 2px; }
        .dates td { padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .items th { background: #0f172a; color: #fff; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        .items td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .items tr:nth-child(even) td { background: #f8fafc; }
        .text-right { text-align: right; }
        .tag { display: inline-block; background: #eef2ff; color: #3730a3; border-radius: 6px; padding: 1px 6px; font-size: 9px; margin: 1px 2px 1px 0; }
        .totals { width: 45%; margin-left: 55%; margin-top: 14px; }
        .totals td { padding: 5px 10px; }
        .grand td { background: #0f172a; color: #fff; font-size: 13px; font-weight: bold; padding: 9px 10px; }
        .footer { margin-top: 26px; text-align: center; color: #64748b; font-size: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .footer-note { margin-top: 4px; color: #0f172a; font-weight: bold; }
    </style>
</head>
<body>
@php
    $store    = \App\Models\StoreSetting::getSettings();
    $currency = \App\Support\Money::symbol();
    $money    = fn ($v) => $currency . number_format((float) $v, 2);
    $refunded = (float) $order->totalRefunded();

    // Who the order is for: registered app user → POS customer → guest
    $customer = $order->user;
    $custName = $customer?->name ?: trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? '')) ?: 'Walk-in customer';
    $custEmail = $customer?->email ?? ($order->customer->email ?? null);
    $custPhone = $customer?->phone_number ?? ($order->customer->phone_number ?? null);

    $ship = $order->shippingAddress;
    $bill = $order->billingAddress ?? $ship;

    $logoPath = ($store && $store->logo) ? public_path('storage/' . $store->logo) : null;
    $logoOk   = $logoPath && is_file($logoPath);

    $variationText = function ($v) {
        if (empty($v)) return [];
        if (is_string($v)) { $v = json_decode($v, true); }
        return is_array($v) ? $v : [];
    };

    $orderDate = $order->order_date ?? $order->created_at;
    $subtotal  = $order->total_amount - ($order->shipping_cost ?? 0) - ($order->tax_cost ?? 0);
    $isPaid    = ($order->payment_status ?? '') === 'paid';
@endphp

    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td style="width:60%; vertical-align:middle;">
                    <table>
                        <tr>
                            @if($logoOk)
                                <td style="width:150px; vertical-align:middle;"><img src="{{ $logoPath }}" class="logo" alt=""></td>
                            @endif
                            <td style="vertical-align:middle;">
                                <h1>{{ $store->store_name ?? config('app.name') }}</h1>
                                @if($store && $store->motto)<div class="muted-light">{{ $store->motto }}</div>@endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width:40%; vertical-align:middle;">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number">#{{ $order->invoice_number ?? substr($order->id, 0, 8) }}</div>
                </td>
            </tr>
        </table>
        <table class="info">
            <tr>
                <td style="width:50%;">
                    <div class="label">From</div>
                    <div>{{ $store->address ?? '' }}</div>
                    <div>{{ $store->phone ?? '' }}</div>
                    <div>{{ $store->email ?? '' }}</div>
                    @if($store && $store->tax_id)<div>Tax ID: {{ $store->tax_id }}</div>@endif
                </td>
                <td style="width:50%;">
                    <div class="label">Payment</div>
                    <span class="badge {{ $isPaid ? 'paid' : 'unpaid' }}">{{ ucfirst($order->payment_status ?? 'unpaid') }}</span>
                    <div style="margin-top:4px;">Method: {{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</div>
                    @if($order->paid_at)<div>Paid on: {{ $order->paid_at->format('d M Y, H:i') }}</div>@endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Dates -->
    <table class="dates section">
        <tr>
            <td><strong>Order date:</strong> {{ $orderDate->format('F d, Y') }}</td>
            <td><strong>Status:</strong> {{ ucfirst($order->status ?? '') }}</td>
            <td class="text-right"><strong>Items:</strong> {{ $order->items->sum('quantity') }}</td>
        </tr>
    </table>

    <!-- Addresses -->
    <table class="section">
        <tr>
            <td style="width:49%; vertical-align:top;">
                <div class="card">
                    <h3>Bill to</h3>
                    @if($bill)
                        <p><strong>{{ $bill->name ?: $custName }}</strong></p>
                        <p>{{ $bill->street }}</p>
                        @if(!empty($bill->landmark))<p>Landmark: {{ $bill->landmark }}</p>@endif
                        <p>{{ collect([$bill->city, $bill->lga ?? null, $bill->state, $bill->postal_code])->filter()->implode(', ') }}</p>
                        <p>{{ $bill->country }}</p>
                        @if($bill->phone_number)<p>Tel: {{ $bill->phone_number }}</p>@endif
                    @else
                        <p><strong>{{ $custName }}</strong></p>
                        @if($custEmail)<p>{{ $custEmail }}</p>@endif
                        @if($custPhone)<p>Tel: {{ $custPhone }}</p>@endif
                    @endif
                </div>
            </td>
            <td style="width:2%;"></td>
            <td style="width:49%; vertical-align:top;">
                <div class="card">
                    <h3>Ship to</h3>
                    @if($ship)
                        <p><strong>{{ $ship->name ?: $custName }}</strong></p>
                        <p>{{ $ship->street }}</p>
                        @if(!empty($ship->landmark))<p>Landmark: {{ $ship->landmark }}</p>@endif
                        <p>{{ collect([$ship->city, $ship->lga ?? null, $ship->state, $ship->postal_code])->filter()->implode(', ') }}</p>
                        <p>{{ $ship->country }}</p>
                        @if($ship->phone_number)<p>Tel: {{ $ship->phone_number }}</p>@endif
                    @else
                        <p><strong>{{ $custName }}</strong></p>
                        <p>Pickup / no delivery address</p>
                        @if($custPhone)<p>Tel: {{ $custPhone }}</p>@endif
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Items -->
    <table class="items section">
        <thead>
            <tr>
                <th style="width:40%;">Item</th>
                <th style="width:24%;">Variation</th>
                <th class="text-right" style="width:8%;">Qty</th>
                <th class="text-right" style="width:14%;">Unit price</th>
                <th class="text-right" style="width:14%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->title ?? 'Product' }}</strong>
                        @if($item->sku)<br><span style="color:#64748b; font-size:9px;">SKU: {{ $item->sku }}</span>@endif
                    </td>
                    <td>
                        @forelse($variationText($item->selected_variation) as $key => $val)
                            <span class="tag">{{ ucfirst((string) $key) }}: {{ is_array($val) ? implode(', ', $val) : $val }}</span>
                        @empty
                            -
                        @endforelse
                    </td>
                    <td class="text-right">{{ $item->quantity ?? 0 }}</td>
                    <td class="text-right">{{ $money($item->price ?? 0) }}</td>
                    <td class="text-right">{{ $money(($item->price ?? 0) * ($item->quantity ?? 0)) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">No items found in this order</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals">
        <tr><td>Subtotal</td><td class="text-right">{{ $money($subtotal) }}</td></tr>
        <tr><td>Shipping</td><td class="text-right">{{ $money($order->shipping_cost ?? 0) }}</td></tr>
        <tr><td>Tax</td><td class="text-right">{{ $money($order->tax_cost ?? 0) }}</td></tr>
        @if($refunded > 0)
            <tr style="color:#dc2626;"><td>Refunded</td><td class="text-right">-{{ $money($refunded) }}</td></tr>
        @endif
        <tr class="grand"><td>Grand total</td><td class="text-right">{{ $money($order->total_amount ?? 0) }}</td></tr>
    </table>

    <div class="footer">
        <div>{{ $store->website ?? config('app.url') }}</div>
        <div>{{ $store->email ?? '' }}{{ ($store->email ?? false) && ($store->phone ?? false) ? ' | ' : '' }}{{ $store->phone ?? '' }}</div>
        <div class="footer-note">{{ ($store && $store->footer_note) ? $store->footer_note : 'Thank you for shopping with us!' }}</div>
    </div>
</body>
</html>
