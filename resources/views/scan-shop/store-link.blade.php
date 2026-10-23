<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scan &amp; Shop — GozakMart</title>
    <style>
        body { margin: 0; font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: #f6f7f6; color: #1a1a1a; display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 16px; }
        .card { background: #fff; max-width: 420px; width: 100%; border-radius: 16px; padding: 28px 22px; text-align: center; box-shadow: 0 6px 24px rgba(0,0,0,.06); }
        h1 { font-size: 24px; margin: 0 0 8px; }
        p { color: #555; line-height: 1.5; }
        a.btn { display: inline-block; margin-top: 12px; background: #4CAF50; color: #fff; text-decoration: none; padding: 12px 22px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
<div class="card">
    <h1>Scan &amp; Shop{{ $store ? ' at ' . $store->name : '' }}</h1>
    <p>Open the <strong>GozakMart</strong> app, tap <strong>Scan &amp; Shop</strong>, and scan this code again to start shopping in this store.</p>
    <a class="btn" href="https://play.google.com/store/apps/details?id=ng.gozakmart.shop">Get the GozakMart app</a>
</div>
</body>
</html>
