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
                <strong>Invoice #:</strong> {{ substr($order->id, 0, 10) }}<br>
                <strong>Order Date:</strong> {{ $order->created_at->format('d M Y') }}<br>
                <strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}
            </div>
            <div class="info-cell text-right">
                <span class="badge {{ $order->payment_status == 'paid' ? 'status-paid' : 'status-unpaid' }}">
                    {{ ucfirst($order->payment_status) }}
                </span>
                <h3 style="margin: 10px 0 0;">Invoice</h3>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <strong>Bill To:</strong><br>
                {{ $order->billingAddress->name ?? $order->shippingAddress->name }}<br>
                {{ $order->billingAddress->street ?? $order->shippingAddress->street }}<br>
                {{ $order->billingAddress->city ?? $order->shippingAddress->city }},
                {{ $order->billingAddress->country ?? $order->shippingAddress->country }}<br>
                <strong>Phone:</strong> {{ $order->billingAddress->phone_number ?? 'N/A' }}
            </div>
            <div class="info-cell">
                <strong>Ship To:</strong><br>
                {{ $order->shippingAddress->name }}<br>
                {{ $order->shippingAddress->street }}<br>
                {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->country }}<br>
                <strong>Phone:</strong> {{ $order->shippingAddress->phone_number ?? 'N/A' }}
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
            @foreach($order->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->title }}</td>
                <td>
                    @if($item->selected_variation)
                        @php $attrs = json_decode($item->selected_variation, true); @endphp
                        @foreach($attrs as $key => $val)
                            <strong>{{ ucfirst($key) }}:</strong> {{ $val }}<br>
                        @endforeach
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format($item->price, 2) }}</td>
                <td>${{ number_format($item->price * $item->quantity, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table style="width: 40%; margin-left: auto;">
        <tr>
            <td style="border: none;"><strong>Subtotal</strong></td>
            <td style="border: none;" class="text-right">${{ number_format($order->total, 2) }}</td>
        </tr>
        <tr>
            <td style="border: none;"><strong>Shipping</strong></td>
            <td style="border: none;" class="text-right">${{ number_format($order->shipping_cost, 2) }}</td>
        </tr>
        <tr>
            <td style="border: none;"><strong>Tax</strong></td>
            <td style="border: none;" class="text-right">${{ number_format($order->tax_cost, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Grand Total</strong></td>
            <td class="text-right"><strong>${{ number_format($order->total_amount, 2) }}</strong></td>
        </tr>
    </table>

    <div class="footer">
        <p>Thank you for your business!</p>
        <p>{{ config('app.url') }} | support@yourstore.com | +1 234 567 890</p>
    </div>

</body>
</html>