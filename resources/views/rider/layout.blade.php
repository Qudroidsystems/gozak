<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rider') · GozakMart</title>
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css') }}" rel="stylesheet">
    <style>
        :root { --rd-ink: #0f172a; --rd-muted: #64748b; --rd-line: #e2e8f0; --rd-bg: #f1f5f9; --rd-accent: #0d9488; }
        body { background: var(--rd-bg); color: var(--rd-ink); font-size: 15px; padding-bottom: env(safe-area-inset-bottom); }
        .rd-top { background: var(--rd-ink); color: #fff; padding: 12px 16px; position: sticky; top: 0; z-index: 10; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .rd-top a, .rd-top button { color: #fff; text-decoration: none; }
        .rd-top .who { font-size: 12px; opacity: .75; }
        .rd-wrap { max-width: 640px; margin: 0 auto; padding: 14px 12px 40px; }
        .rd-card { background: #fff; border: 1px solid var(--rd-line); border-radius: 14px; padding: 14px; margin-bottom: 12px; }
        .rd-route { background: var(--rd-ink); color: #fff; border-radius: 10px; padding: 4px 10px; font-weight: 700; letter-spacing: .5px; display: inline-block; }
        .rd-stopno { width: 44px; height: 44px; border-radius: 12px; background: var(--rd-ink); color: #fff; font-size: 20px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex: 0 0 44px; }
        .rd-stop.done { opacity: .6; }
        .rd-stop.next { border: 2px solid var(--rd-accent); box-shadow: 0 6px 18px rgba(13,148,136,.15); }
        .rd-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .rd-actions a, .rd-actions button { border-radius: 10px; padding: 10px 6px; font-weight: 600; font-size: 13px; }
        .rd-collect { background: #fef3c7; border: 1px solid #f59e0b; border-radius: 10px; padding: 8px 12px; font-weight: 700; }
        .rd-code { font-size: 30px; letter-spacing: 14px; text-align: center; font-weight: 800; font-family: ui-monospace, monospace; }
        .rd-tag { display: inline-block; background: var(--rd-ink); color: #fff; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; margin: 0 2px 2px 0; }
        .rd-parcel { font-family: ui-monospace, monospace; font-size: 12px; background: var(--rd-bg); border-radius: 6px; padding: 2px 6px; display: inline-block; margin: 0 4px 4px 0; }
        .rd-muted { color: var(--rd-muted); font-size: 13px; }
        .btn-lg { border-radius: 12px; }
    </style>
</head>
<body>
    <div class="rd-top">
        <a href="{{ route('rider.index') }}" class="fw-bold"><i class="ri-e-bike-2-line"></i> GozakMart Rider</a>
        <div class="text-end">
            <div class="who">{{ auth()->user()?->full_name }}</div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-link p-0 small" style="font-size:12px;">Sign out</button></form>
        </div>
    </div>
    <div class="rd-wrap">
        @if(session('success'))<div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> {{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger"><i class="ri-error-warning-line"></i> {{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
