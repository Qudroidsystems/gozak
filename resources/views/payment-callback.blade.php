<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #c0250d 0%, #a41f10 100%);
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 400px;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .checkmark {
            width: 40px;
            height: 40px;
            border: 4px solid white;
            border-radius: 50%;
            position: relative;
        }
        .checkmark:after {
            content: '';
            position: absolute;
            left: 10px;
            top: 5px;
            width: 10px;
            height: 18px;
            border: solid white;
            border-width: 0 4px 4px 0;
            transform: rotate(45deg);
        }
        h1 {
            color: #1f2937;
            margin: 0 0 10px;
            font-size: 28px;
        }
        p {
            color: #6b7280;
            margin: 0 0 30px;
            font-size: 16px;
        }
        .reference {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .reference strong {
            display: block;
            color: #4b5563;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .reference span {
            color: #1f2937;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .note {
            font-size: 14px;
            color: #9ca3af;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <div class="checkmark"></div>
        </div>
        <h1>Payment Successful!</h1>
        <p>Your payment has been processed successfully</p>
        
        @if(request('reference'))
        <div class="reference">
            <strong>TRANSACTION REFERENCE</strong>
            <span>{{ request('reference') }}</span>
        </div>
        @endif
        
        <p class="note">You can close this page and return to the app</p>
    </div>
</body>
</html>