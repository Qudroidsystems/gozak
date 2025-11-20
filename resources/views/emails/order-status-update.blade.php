<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Update - {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: #059669; color: white; padding: 30px 20px; text-align: center; }
        .content { padding: 30px; background: #f9f9f9; }
        .status-card { background: white; padding: 25px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .barcode-section { text-align: center; margin: 20px 0; padding: 15px; background: #f8fafc; border-radius: 8px; }
        .footer { text-align: center; margin-top: 30px; padding: 20px; font-size: 12px; color: #666; border-top: 1px solid #eee; }
        .button { background: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 28px;">{{ $statusTitle }}</h1>
        </div>
        
        <div class="content">
            <p style="font-size: 16px;">Hello {{ $order->user->first_name }},</p>
            
            <div class="status-card">
                <h2 style="color: #059669; margin-top: 0;">Order #{{ $orderIdShort }}</h2>
                
                <div style="background: #ecfdf5; padding: 15px; border-radius: 6px; margin: 15px 0;">
                    <strong>New Status:</strong> 
                    <span style="color: #059669; font-weight: bold; font-size: 18px;">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                <p><strong>Updated:</strong> {{ now()->format('F j, Y \a\t g:i A') }}</p>
                
                @if($order->status === 'shipped' && $order->delivery_date)
                <p><strong>Expected Delivery:</strong> {{ $order->delivery_date->format('F j, Y') }}</p>
                @endif

                @if($order->status === 'shipped')
                <div style="background: #f0f9ff; padding: 15px; border-radius: 6px; margin: 15px 0;">
                    <strong>🚚 Your order is on the way!</strong>
                    <p style="margin: 10px 0 0 0;">We've shipped your order and it's making its way to you.</p>
                </div>
                @elseif($order->status === 'delivered')
                <div style="background: #f0fdf4; padding: 15px; border-radius: 6px; margin: 15px 0;">
                    <strong>🎉 Your order has been delivered!</strong>
                    <p style="margin: 10px 0 0 0;">We hope you love your purchase. Thank you for shopping with us!</p>
                </div>
                @endif
                
                <div class="barcode-section">
                    <h4 style="margin-top: 0; color: #059669;">Order Barcode</h4>
                    <img src="{{ $message->embedData($barcodePng, 'barcode.png') }}" 
                         alt="Order Barcode" 
                         style="max-width: 200px; height: auto;">
                    <p style="font-size: 12px; color: #666; margin: 10px 0 0 0;">
                        Keep this barcode handy for order verification
                    </p>
                </div>
            </div>

            <div style="text-align: center; margin: 25px 0;">
                <a href="{{ url('/orders/' . $order->id) }}" class="button">
                    @if($order->status === 'shipped') Track Your Order
                    @else View Order Details
                    @endif
                </a>
            </div>

            <div class="footer">
                <p>Thank you for shopping with {{ config('app.name') }}!</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>