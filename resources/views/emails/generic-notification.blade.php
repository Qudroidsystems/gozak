<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: #6366F1; color: white; padding: 25px 20px; text-align: center; }
        .content { padding: 25px; background: #f9f9f9; }
        .notification-card { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .footer { text-align: center; margin-top: 25px; padding: 15px; font-size: 12px; color: #666; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">{{ $title }}</h1>
        </div>
        
        <div class="content">
            <div class="notification-card">
                <p style="font-size: 16px; margin: 0; white-space: pre-line;">{{ $body }}</p>
                
                @if(!empty($data))
                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                    <h4 style="margin-top: 0; color: #6366F1;">Additional Information:</h4>
                    <ul style="margin-bottom: 0;">
                        @foreach($data as $key => $value)
                            @if(!is_array($value))
                            <li><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <div class="footer">
                <p>This is an automated message from {{ config('app.name') }}. Please do not reply to this email.</p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>