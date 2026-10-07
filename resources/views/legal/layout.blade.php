<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') – Gozak Mart</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;line-height:1.65;margin:0;padding:24px 20px 60px;max-width:820px;margin-left:auto;margin-right:auto;color:#2b2b2b}
        h1{color:#FF6A00;font-size:1.9em;margin-bottom:.2em}
        h2{color:#1f1f1f;font-size:1.25em;margin-top:1.8em;border-bottom:1px solid #eee;padding-bottom:4px}
        .meta{color:#666;font-style:italic;margin-bottom:1.5em}
        ul,ol{padding-left:22px} li{margin-bottom:.4em}
        a{color:#FF6A00} .box{background:#fff6ee;border-left:4px solid #FF6A00;padding:10px 14px;margin:1em 0}
        footer{margin-top:3em;font-size:.9em;color:#777;border-top:1px solid #eee;padding-top:12px}
        nav a{margin-right:14px;font-size:.95em}
    </style>
</head>
<body>
    <nav><a href="/privacy">Privacy Policy</a><a href="/terms">Terms of Use</a><a href="/delete-account">Delete Account</a></nav>
    @yield('content')
    <footer>Gozak Mart is operated by Qudroid Systems. &copy; {{ date('Y') }} Qudroid Systems.</footer>
</body>
</html>
