<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scan &amp; Shop — {{ $location->name }}</title>
    <style>
        :root { --green: #4CAF50; --ink: #1a1a1a; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; color: var(--ink); background: #f4f4f4; }
        .poster { width: 210mm; min-height: 297mm; margin: 16px auto; background: #fff; padding: 22mm 18mm; text-align: center; }
        h1 { font-size: 46px; margin: 0 0 6px; letter-spacing: -1px; }
        h1 span { color: var(--green); }
        .store { font-size: 22px; color: #555; margin-bottom: 26px; }
        .qr { display: inline-block; padding: 18px; border: 6px solid var(--green); border-radius: 22px; }
        .qr svg, .qr canvas, .qr img { width: 92mm !important; height: 92mm !important; display: block; }
        ol { text-align: left; max-width: 140mm; margin: 30px auto 0; font-size: 20px; line-height: 1.6; padding-left: 28px; }
        .foot { margin-top: 28px; color: #777; font-size: 14px; word-break: break-all; }
        .toolbar { text-align: center; margin: 16px; }
        .toolbar button { font-size: 16px; padding: 10px 22px; border: 0; border-radius: 8px; background: var(--green); color: #fff; cursor: pointer; }
        @media print { body { background: #fff; } .toolbar { display: none; } .poster { margin: 0; } }
    </style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print poster</button></div>
<div class="poster">
    <h1>Scan <span>&amp;</span> Shop</h1>
    <div class="store">{{ $location->name }}</div>
    <div class="qr" id="qr">{!! $qrSvg !!}</div>
    <ol>
        <li>Open the <strong>GozakMart</strong> app and tap <strong>Scan &amp; Shop</strong>.</li>
        <li>Scan this code, then scan the barcode on each item you want.</li>
        <li>Pay in the app and go. We deliver your items to your door.</li>
    </ol>
    <div class="foot">{{ $link }}</div>
</div>
@if(!$qrSvg)
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>new QRCode(document.getElementById('qr'), { text: @json($link), width: 360, height: 360 });</script>
@endif
</body>
</html>
