<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, hsl(60, 100%, 100%) 0%, hsl(0, 18%, 96%) 100%);
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        .success-animation {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            position: relative;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .checkmark {
            width: 50px;
            height: 50px;
            position: relative;
        }

        .checkmark:after {
            content: '';
            position: absolute;
            left: 15px;
            top: 8px;
            width: 15px;
            height: 28px;
            border: solid white;
            border-width: 0 5px 5px 0;
            transform: rotate(45deg);
            animation: checkmark 0.3s ease-out 0.3s both;
        }

        @keyframes checkmark {
            from {
                height: 0;
            }
            to {
                height: 28px;
            }
        }

        h1 {
            color: #1f2937;
            margin: 0 0 10px;
            font-size: 28px;
            font-weight: 700;
        }

        p {
            color: #6b7280;
            margin: 0 0 25px;
            font-size: 16px;
            line-height: 1.5;
        }

        .reference {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .reference strong {
            display: block;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .reference span {
            color: #1f2937;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            font-weight: 600;
            word-break: break-all;
        }

        .btn-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #f3f4f6;
        }

        .note {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 20px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
@php
    // Paystack lands here after a payment; OPay lands here via /payment/opay/return with $status.
    $status = $status ?? 'success';
    $ok     = $status === 'success';
    $wait   = $status === 'pending';
@endphp
        <div class="success-animation">
            <div class="success-icon" @unless($ok) style="background: {{ $wait ? '#f59e0b' : '#ef4444' }};" @endunless>
                @if($ok)
                    <div class="checkmark"></div>
                @else
                    <span style="color:#fff;font-size:48px;font-weight:700;line-height:1;">{{ $wait ? '…' : '!' }}</span>
                @endif
            </div>
        </div>
        @if($ok)
            <h1>Payment Successful!</h1>
            <p>Your payment has been processed successfully. Gozak says thank you for your order!</p>
        @elseif($wait)
            <h1>Payment Processing</h1>
            <p>We are still waiting for confirmation of your payment. Your order will update automatically once it is confirmed.</p>
        @else
            <h1>Payment Not Completed</h1>
            <p>Your payment was cancelled or declined. No money was taken — return to the app to try again.</p>
        @endif
        @if(request('reference'))
        <div class="reference">
            <strong>Transaction Reference</strong>
            <span>{{ request('reference') }}</span>
        </div>
        @endif
        <p class="note">
            @if($ok)A receipt has been sent to your email.<br>@endif
            You can safely close this page after returning to the app.
        </p>
    </div>
    {{-- <script>
        function returnToApp() {
            // Send message to Flutter WebView
            if (window.flutter_inappwebview) {
                window.flutter_inappwebview.callHandler('paymentSuccess', {
                    reference: '{{ request("reference") }}',
                    status: 'success'
                });
            }


            // Trigger custom event for WebView
            window.location.href = 'flutter://payment-success?reference={{ request("reference") }}';
        }

       function viewOrders() {
            // Use correct route name for orders
            window.location.href = 'flutter://view-orders?reference={{ request("reference") }}';
        }

        // Auto-send success signal to Flutter (backup)
        setTimeout(function() {
            if (window.PaymentSuccess) {
                window.PaymentSuccess.postMessage('{{ request("reference") }}');
            }
        }, 500);
    </script> --}}
</body>
</html>
