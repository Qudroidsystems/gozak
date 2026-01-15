{{-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - GoZakMart</title>
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
            background: linear-gradient(135deg, #ea4a42 0%, #b24c4c 100%);
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
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            width: 100%;
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

        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .note {
            font-size: 13px;
            color: #9ca3af;
            margin-top: 20px;
            line-height: 1.5;
        }

        .loading {
            display: none;
            margin-top: 15px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .success-check {
            display: none;
            color: #10b981;
            margin-top: 10px;
            font-weight: 600;
        }

        .success-check.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-animation">
            <div class="success-icon">
                <div class="checkmark"></div>
            </div>
        </div>

        <h1>Payment Successful!</h1>
        <p>Your payment has been processed successfully. Thank you for your order!</p>

        @if(isset($reference) && $reference)
        <div class="reference">
            <strong>Transaction Reference</strong>
            <span id="referenceText">{{ $reference }}</span>
        </div>
        @endif

        <div class="btn-container">
            <button id="returnBtn" class="btn btn-primary" onclick="returnToApp()">
                Return to App
            </button>
        </div>

        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p style="margin-top: 10px;">Returning to app...</p>
        </div>

        <div id="successCheck" class="success-check">
            ✓ Successfully returned to app
        </div>

        <p class="note">
            A receipt has been sent to your email.<br>
            You can safely close this page after returning to the app.
        </p>
    </div>

    <script>
        let hasReturned = false;
        let returnAttempts = 0;
        const maxAttempts = 3;

        function showLoading() {
            document.getElementById('returnBtn').disabled = true;
            document.getElementById('loading').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loading').classList.remove('active');
        }

        function showSuccess() {
            document.getElementById('successCheck').classList.add('active');
        }

        function returnToApp() {
            if (hasReturned) return;

            showLoading();
            returnAttempts++;

            const reference = document.getElementById('referenceText')?.textContent || '';

            console.log('Attempting to return to app, attempt:', returnAttempts);
            console.log('Reference:', reference);

            // Try multiple methods to communicate with Flutter

            // Method 1: Flutter InAppWebView (most reliable)
            if (typeof window.flutter_inappwebview !== 'undefined') {
                console.log('Method 1: Using flutter_inappwebview');
                window.flutter_inappwebview.callHandler('paymentSuccess', {
                    reference: reference,
                    status: 'success',
                    timestamp: Date.now()
                }).then(function(result) {
                    console.log('Payment success callback result:', result);
                    hasReturned = true;
                    showSuccess();
                    setTimeout(closeWindow, 1000);
                }).catch(function(error) {
                    console.log('Error calling flutter_inappwebview:', error);
                    tryMethod2(reference);
                });
            } else {
                tryMethod2(reference);
            }
        }

        function tryMethod2(reference) {
            // Method 2: WebView JavaScript Channel
            console.log('Method 2: Trying WebView channel');

            // Create and dispatch a custom event
            const event = new CustomEvent('paymentSuccess', {
                detail: {
                    reference: reference,
                    status: 'success'
                }
            });
            window.dispatchEvent(event);

            // Try postMessage
            if (window.parent) {
                window.parent.postMessage({
                    type: 'paymentSuccess',
                    reference: reference,
                    status: 'success'
                }, '*');
            }

            // Try the PaymentSuccess channel
            if (typeof PaymentSuccess !== 'undefined') {
                PaymentSuccess.postMessage(JSON.stringify({
                    reference: reference,
                    status: 'success'
                }));
            }

            setTimeout(tryMethod3.bind(null, reference), 500);
        }

        function tryMethod3(reference) {
            // Method 3: URL Scheme (fallback)
            console.log('Method 3: Trying URL schemes');

            // Try multiple URL schemes
            const schemes = [
                `gozakmart://payment-success?reference=${reference}&status=success`,
                `flutter://payment-success?reference=${reference}&status=success`,
                `myapp://payment-success?reference=${reference}&status=success`,
                `app://payment-success?reference=${reference}&status=success`
            ];

            let currentScheme = 0;

            function tryNextScheme() {
                if (currentScheme < schemes.length) {
                    console.log('Trying scheme:', schemes[currentScheme]);
                    window.location.href = schemes[currentScheme];
                    currentScheme++;
                    setTimeout(tryNextScheme, 300);
                } else {
                    // All schemes tried, show success and try to close
                    hasReturned = true;
                    showSuccess();
                    hideLoading();
                    setTimeout(closeWindow, 2000);
                }
            }

            tryNextScheme();
        }

        function closeWindow() {
            console.log('Attempting to close window');
            // Try to close the window
            if (window.close && typeof window.close === 'function') {
                try {
                    window.close();
                } catch (e) {
                    console.log('Could not close window:', e);
                }
            }

            // Fallback: Redirect to about:blank
            setTimeout(function() {
                window.location.href = 'about:blank';
            }, 1000);
        }

        // Auto-return after 8 seconds if user doesn't click
        setTimeout(function() {
            if (!hasReturned && returnAttempts === 0) {
                console.log('Auto-returning to app after timeout');
                returnToApp();
            }
        }, 8000);

        // Listen for visibility changes (user switched apps)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && !hasReturned) {
                console.log('Page became hidden, user may have switched to app');
                hasReturned = true;
                // Send success message even if page is hidden
                if (typeof window.flutter_inappwebview !== 'undefined') {
                    window.flutter_inappwebview.callHandler('paymentSuccess', {
                        reference: document.getElementById('referenceText')?.textContent || '',
                        status: 'success'
                    });
                }
            }
        });

        // Listen for beforeunload (page is being unloaded)
        window.addEventListener('beforeunload', function() {
            if (!hasReturned) {
                console.log('Page is unloading, sending final success message');
                // Try one last time to send the message
                if (typeof window.flutter_inappwebview !== 'undefined') {
                    window.flutter_inappwebview.callHandler('paymentSuccess', {
                        reference: document.getElementById('referenceText')?.textContent || '',
                        status: 'success'
                    });
                }
            }
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Success page loaded');
            console.log('Reference:', document.getElementById('referenceText')?.textContent);

            // Auto-start return if reference exists and it's likely from WebView
            if (window.location.search.includes('reference=') &&
                (navigator.userAgent.includes('Flutter') ||
                 navigator.userAgent.includes('WebView') ||
                 window.innerWidth < 800)) {
                console.log('Detected WebView, auto-returning in 2 seconds');
                setTimeout(returnToApp, 2000);
            }
        });
    </script>
</body>
</html> --}}
