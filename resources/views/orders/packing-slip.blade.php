<!DOCTYPE html>
<html>
<head>
    <title>Packing Slip - {{ $order->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 40px; }
        .header { text-align: center; border-bottom: 3px solid #000; padding-bottom: 20px; margin-bottom: 40px; }
        .info { display: flex; justify-content: space-between; margin: 40px 0; }
        table { width: 100%; border-collapse: collapse; margin: 30px 0; }
        th, td { border: 1px solid #000; padding: 12px; text-align: left; }
        th { background: #f0f0f0; }
        .footer { margin-top: 100px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PACKING SLIP</h1>
        <h3>Order #{{ $order->invoice_number }}</h3>
    </div>

    <div class="info">
        <div>
            <strong>Ship To:</strong><br>
            {{ $order->shippingAddress->name }}<br>
            {{ $order->shippingAddress->street }}<br>
            {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->country }}
        </div>
        <div>
            <strong>Order Date:</strong> {{ $order->created_at->format('d M Y') }}<br>
            <strong>Items:</strong> {{ $order->items_count }}
        </div>
    </div>

    <table>
        <thead>
            <tr><th>Product</th><th>Qty</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->title }}</td>
                <td>{{ $item->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Thank you for your order!
    </div>
</body>
</html>