<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ substr($order->id, 0, 10) }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 40px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 20px;
        }
        .logo {
            max-height: 80px;
            margin-bottom: 10px;
        }
        h1 { margin: 0; color: #0d6efd; }
        .info-grid {
            display: table;
            width: 100%;
            margin: 30px 0;
        }
        .info-row {
            display: table-row;
        }
        .info-cell {
            display: table-cell;
            padding: 8px 0;
            width: 50%;
        }
        .info-cell strong {
            display: inline-block;
            width: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .text-right { text-align: right; }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 60px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-paid { background: #d4edda; color: #155724; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <div class="header">
        @if(file_exists(public_path('img/logo.png')))
            <img src="{{ public_path('img/logo.png') }}" class="logo" alt="Logo">
        @else
            <h1>{{ config('app.name') }}</h1>
        @endif
        <p>Official Invoice</p>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <strong>Invoice #:</strong> {{ $order->invoice_number ?? substr($order->id, 0, 10) }}<br>
                <strong>Order Date:</strong> {{ $order->created_at->format('d M Y') }}<br>
                <strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}
            </div>
            <div class="info-cell text-right">
                <span class="badge {{ ($order->payment_status ?? '') == 'paid' ? 'status-paid' : 'status-unpaid' }}">
                    {{ ucfirst($order->payment_status ?? 'unknown') }}
                </span>
                <h3 style="margin: 10px 0 0;">Invoice</h3>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <strong>Bill To:</strong><br>
                @if($order->billingAddress)
                    {{ $order->billingAddress->name ?? 'N/A' }}<br>
                    {{ $order->billingAddress->street ?? 'N/A' }}<br>
                    {{ $order->billingAddress->city ?? 'N/A' }},
                    {{ $order->billingAddress->country ?? 'N/A' }}<br>
                    <strong>Phone:</strong> {{ $order->billingAddress->phone_number ?? 'N/A' }}
                @elseif($order->shippingAddress)
                    {{ $order->shippingAddress->name ?? 'N/A' }}<br>
                    {{ $order->shippingAddress->street ?? 'N/A' }}<br>
                    {{ $order->shippingAddress->city ?? 'N/A' }},
                    {{ $order->shippingAddress->country ?? 'N/A' }}<br>
                    <strong>Phone:</strong> {{ $order->shippingAddress->phone_number ?? 'N/A' }}
                @else
                    <span class="text-muted">No billing address available</span><br>
                    <span class="text-muted">-</span><br>
                    <span class="text-muted">-</span><br>
                    <strong>Phone:</strong> N/A
                @endif
            </div>
            <div class="info-cell">
                <strong>Ship To:</strong><br>
                @if($order->shippingAddress)
                    {{ $order->shippingAddress->name ?? 'N/A' }}<br>
                    {{ $order->shippingAddress->street ?? 'N/A' }}<br>
                    {{ $order->shippingAddress->city ?? 'N/A' }}, {{ $order->shippingAddress->country ?? 'N/A' }}<br>
                    <strong>Phone:</strong> {{ $order->shippingAddress->phone_number ?? 'N/A' }}
                @else
                    <span class="text-muted">No shipping address available</span><br>
                    <span class="text-muted">-</span><br>
                    <span class="text-muted">-</span><br>
                    <strong>Phone:</strong> N/A
                @endif
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Variation</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->title ?? 'N/A' }}</td>
                <td>
                    @if(!empty($item->selected_variation))
                        @php $attrs = json_decode($item->selected_variation, true); @endphp
                        @if(is_array($attrs) && count($attrs) > 0)
                            @foreach($attrs as $key => $val)
                                <strong>{{ ucfirst($key) }}:</strong> {{ $val }}<br>
                            @endforeach
                        @else
                            -
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->quantity ?? 0 }}</td>
                <td>${{ number_format($item->price ?? 0, 2) }}</td>
                <td>${{ number_format(($item->price ?? 0) * ($item->quantity ?? 0), 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No items found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table style="width: 40%; margin-left: auto;">
        <tr>
            <td style="border: none;"><strong>Subtotal</strong></td>
            <td style="border: none;" class="text-right">${{ number_format($order->total ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="border: none;"><strong>Shipping</strong></td>
            <td style="border: none;" class="text-right">${{ number_format($order->shipping_cost ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td style="border: none;"><strong>Tax</strong></td>
            <td style="border: none;" class="text-right">${{ number_format($order->tax_cost ?? 0, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Grand Total</strong></td>
            <td class="text-right"><strong>${{ number_format($order->total_amount ?? 0, 2) }}</strong></td>
        </tr>
    </table>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>{{ config('app.url') }} | support@yourstore.com | +1 234 567 890</p>
    </div>

</body>
</html>
