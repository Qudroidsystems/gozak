<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Packing Slip - {{ $order->invoice_number ?? substr($order->id, 0, 8) }}</title>
    <style>
        @page { margin: 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111; margin: 0; }
        .header { text-align: center; border-bottom: 3px solid #000; padding-bottom: 14px; margin-bottom: 24px; }
        .header h1 { margin: 0 0 4px; font-size: 22px; }
        .header h3 { margin: 0; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; }
        .info td { vertical-align: top; width: 50%; padding-bottom: 20px; }
        .items th, .items td { border: 1px solid #000; padding: 8px 10px; text-align: left; }
        .items th { background: #f0f0f0; }
        .check { width: 60px; text-align: center; }
        .footer { margin-top: 60px; text-align: center; font-size: 11px; color: #444; }
        .sign td { padding-top: 50px; width: 50%; }
        .line { border-top: 1px solid #000; width: 80%; padding-top: 4px; font-size: 10px; }
    </style>
</head>
<body>
@php
    // Shipping address can be missing (POS / pickup orders) — fall back to billing, then the customer.
    $addr     = $order->shippingAddress ?? $order->billingAddress;
    $custName = $order->user?->name ?: trim(($order->customer->first_name ?? '') . ' ' . ($order->customer->last_name ?? '')) ?: 'Walk-in customer';
    $custPhone = $order->user?->phone_number ?? ($order->customer->phone_number ?? null);
    $variation = function ($v) {
        if (empty($v)) return '';
        if (is_string($v)) { $v = json_decode($v, true); }
        if (!is_array($v)) return '';
        return collect($v)->map(fn ($val, $k) => ucfirst((string) $k) . ': ' . (is_array($val) ? implode(', ', $val) : $val))->implode(' · ');
    };
@endphp

    <div class="header">
        <h1>PACKING SLIP</h1>
        <h3>Order #{{ $order->invoice_number ?? substr($order->id, 0, 8) }}</h3>
    </div>

    <table class="info">
        <tr>
            <td>
                <strong>Ship To:</strong><br>
                @if($addr)
                    {{ $addr->name ?: $custName }}<br>
                    {{ $addr->street }}<br>
                    @if(!empty($addr->landmark))<em>Landmark: {{ $addr->landmark }}</em><br>@endif
                    {{ collect([$addr->city, $addr->lga ?? null, $addr->state])->filter()->implode(', ') }}{{ $addr->country ? ', ' . $addr->country : '' }}<br>
                    @if($addr->phone_number)Tel: {{ $addr->phone_number }}@endif
                    @if(!empty($addr->alternate_phone)) / {{ $addr->alternate_phone }}@endif
                @else
                    {{ $custName }}<br>
                    <em>No delivery address (pickup)</em><br>
                    @if($custPhone)Tel: {{ $custPhone }}@endif
                @endif
            </td>
            <td style="text-align:right;">
                <strong>Order Date:</strong> {{ ($order->order_date ?? $order->created_at)->format('d M Y') }}<br>
                <strong>Items:</strong> {{ $order->items->sum('quantity') }} ({{ $order->items->count() }} lines)<br>
                <strong>Payment:</strong> {{ ucfirst($order->payment_status ?? 'unpaid') }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr><th>Product</th><th>SKU</th><th>Qty</th><th class="check">Packed</th></tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
            <tr>
                <td>
                    {{ $item->title }}
                    @if($v = $variation($item->selected_variation))<br><small>{{ $v }}</small>@endif
                </td>
                <td>{{ $item->sku ?? '-' }}</td>
                <td>{{ $item->quantity }}</td>
                <td class="check">&#9744;</td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;">No items</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td><div class="line">Packed by</div></td>
            <td><div class="line">Received by (customer)</div></td>
        </tr>
    </table>

    <div class="footer">Thank you for your order!</div>
</body>
</html>
