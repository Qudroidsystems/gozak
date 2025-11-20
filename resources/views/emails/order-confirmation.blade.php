<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: #4F46E5; color: white; padding: 30px 20px; text-align: center; }
        .content { padding: 30px; background: #f9f9f9; }
        .order-card { background: white; padding: 25px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .barcode-section { text-align: center; margin: 25px 0; padding: 20px; background: #f8fafc; border-radius: 8px; }
        .footer { text-align: center; margin-top: 30px; padding: 20px; font-size: 12px; color: #666; border-top: 1px solid #eee; }
        .button { background: #4F46E5; color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; }
        .item-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
        .item-row:last-child { border-bottom: none; }
        .item-details { flex: 2; }
        .item-price { flex: 1; text-align: right; }
        .total-row { display: flex; justify-content: space-between; padding: 15px 0; font-weight: bold; font-size: 18px; border-top: 2px solid #4F46E5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">Order Confirmed! 🎉</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Thank you for your purchase, {{ $order->user->first_name }}!</p>
        </div>
        
        <div class="content">
            <p style="font-size: 16px; margin-bottom: 25px;">Your order has been confirmed and is being processed. We'll notify you when it ships.</p>
            
            <div class="order-card">
                <h2 style="color: #4F46E5; margin-top: 0;">Order #{{ $orderIdShort }}</h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <strong>Order Date:</strong><br>
                        {{ $order->created_at->format('F j, Y \a\t g:i A') }}
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <span style="color: #10B981; font-weight: bold;">{{ ucfirst($order->status) }}</span>
                    </div>
                </div>

                <div class="barcode-section">
                    <h3 style="margin-top: 0; color: #4F46E5;">Your Order Barcode</h3>
                    <img src="{{ $message->embedData($barcodePng, 'barcode.png') }}" 
                         alt="Order Barcode" 
                         style="max-width: 250px; height: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <p style="font-size: 14px; color: #666; margin: 15px 0 0 0;">
                        Scan this barcode for quick order lookup at our store or for delivery verification.
                    </p>
                </div>

                <h3 style="color: #4F46E5; margin-bottom: 15px;">Order Items</h3>
                @foreach($order->items as $item)
                <div class="item-row">
                    <div class="item-details">
                        <strong>{{ $item->title }}</strong>
                        @if($item->brand_name)
                        <br><small style="color: #666;">Brand: {{ $item->brand_name }}</small>
                        @endif
                        @if($item->selected_variation)
                        <br><small style="color: #666;">
                            Variation: {{ implode(', ', json_decode($item->selected_variation, true) ?? []) }}
                        </small>
                        @endif
                        <br><small>Quantity: {{ $item->quantity }}</small>
                    </div>
                    <div class="item-price">
                        ₦{{ number_format($item->price * $item->quantity, 2) }}
                    </div>
                </div>
                @endforeach

                <div class="total-row">
                    <div>Total Amount:</div>
                    <div>₦{{ number_format($order->total_amount, 2) }}</div>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/orders/' . $order->id) }}" class="button">
                    View Order Details & Track Shipping
                </a>
            </div>

            <div style="background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #4F46E5;">
                <h4 style="margin-top: 0; color: #4F46E5;">What's Next?</h4>
                <ul style="margin-bottom: 0;">
                    <li>We'll prepare your order for shipment</li>
                    <li>You'll receive a notification when your order ships</li>
                    <li>Track your order using the link above</li>
                </ul>
            </div>

            <div class="footer">
                <p>If you have any questions about your order, please contact our support team.</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>