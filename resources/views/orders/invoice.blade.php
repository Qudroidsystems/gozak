<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->invoice_number ?? substr($order->id, 0, 8) }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            padding: 40px;
            color: #1e293b;
            line-height: 1.5;
        }

        .invoice-container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .invoice-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 40px;
            color: white;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            max-height: 60px;
            width: auto;
            background: white;
            border-radius: 12px;
            padding: 8px;
        }

        .store-info h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .store-info .motto {
            color: #94a3b8;
            font-size: 14px;
        }

        .invoice-badge {
            text-align: right;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: 700;
            color: #fbbf24;
            margin-bottom: 8px;
        }

        .invoice-number {
            font-size: 18px;
            color: #e2e8f0;
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 100px;
            display: inline-block;
        }

        .header-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .info-block {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 16px;
        }

        .info-block h3 {
            color: #94a3b8;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .info-block p {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-block i {
            color: #fbbf24;
            font-style: normal;
            width: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 100px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .status-paid {
            background: #05966920;
            color: #059669;
            border: 1px solid #059669;
        }

        .status-unpaid {
            background: #dc262620;
            color: #dc2626;
            border: 1px solid #dc2626;
        }

        .dates-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 30px 40px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .date-label {
            color: #64748b;
            font-size: 14px;
        }

        .date-value {
            font-weight: 600;
            color: #1e293b;
        }

        .addresses-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
            background: white;
        }

        .address-card {
            background: #f8fafc;
            padding: 25px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .address-card h3 {
            color: #0f172a;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .address-card h3:before {
            content: '';
            width: 4px;
            height: 20px;
            background: #fbbf24;
            border-radius: 4px;
        }

        .address-card p {
            color: #475569;
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .address-card .phone {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e1;
            color: #0f172a;
            font-weight: 500;
        }

        .items-section {
            padding: 0 40px 40px 40px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .items-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            text-align: left;
        }

        .items-table td {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .product-title {
            font-weight: 600;
            color: #0f172a;
        }

        .variation-tag {
            display: inline-block;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            color: #475569;
            margin-right: 4px;
        }

        .totals-section {
            padding: 0 40px 40px 40px;
            display: flex;
            justify-content: flex-end;
        }

        .totals-card {
            width: 350px;
            background: #f8fafc;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #e2e8f0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .total-row:last-child {
            border-bottom: none;
        }

        .grand-total {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            padding-top: 15px;
            margin-top: 5px;
        }

        .grand-total .amount {
            color: #059669;
            font-size: 24px;
        }

        .footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 30px 40px;
            text-align: center;
            font-size: 14px;
        }

        .footer p {
            margin-bottom: 5px;
        }

        .footer a {
            color: #fbbf24;
            text-decoration: none;
        }

        .footer-note {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #1e293b;
            color: #cbd5e1;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $settings = \App\Models\StoreSetting::getSettings();
        $currency = $settings->currency_symbol ?? '$';
    @endphp

    <div class="invoice-container">
        <!-- Header with Store Info -->
        <div class="invoice-header">
            <div class="header-top">
                <div class="logo-area">
                    @if($settings && $settings->logo)
                        <img src="{{ public_path('storage/' . $settings->logo) }}" class="logo" alt="{{ $settings->store_name }}">
                    @endif
                    <div class="store-info">
                        <h1>{{ $settings->store_name ?? config('app.name') }}</h1>
                        @if($settings && $settings->motto)
                            <div class="motto">{{ $settings->motto }}</div>
                        @endif
                    </div>
                </div>
                <div class="invoice-badge">
                    <div class="invoice-title">INVOICE</div>
                    <div class="invoice-number">#{{ $order->invoice_number ?? substr($order->id, 0, 8) }}</div>
                </div>
            </div>

            <div class="header-grid">
                <div class="info-block">
                    <h3>FROM</h3>
                    <p><i>🏢</i> {{ $settings->address ?? 'N/A' }}</p>
                    <p><i>📞</i> {{ $settings->phone ?? 'N/A' }}</p>
                    <p><i>✉️</i> {{ $settings->email ?? 'N/A' }}</p>
                    @if($settings && $settings->tax_id)
                        <p><i>🔖</i> Tax ID: {{ $settings->tax_id }}</p>
                    @endif
                </div>
                <div class="info-block">
                    <h3>PAYMENT STATUS</h3>
                    <span class="status-badge {{ ($order->payment_status ?? '') == 'paid' ? 'status-paid' : 'status-unpaid' }}">
                        {{ ucfirst($order->payment_status ?? 'unknown') }}
                    </span>
                    <p><i>💳</i> Method: {{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'N/A')) }}</p>
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="dates-section">
            <div class="date-item">
                <span class="date-label">Order Date:</span>
                <span class="date-value">{{ $order->created_at->format('F d, Y') }}</span>
            </div>
            <div class="date-item">
                <span class="date-label">Due Date:</span>
                <span class="date-value">{{ $order->created_at->addDays(30)->format('F d, Y') }}</span>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses-section">
            <!-- Bill To -->
            <div class="address-card">
                <h3>BILL TO</h3>
                @if($order->billingAddress)
                    <p><strong>{{ $order->billingAddress->name ?? $order->user->first_name . ' ' . $order->user->last_name }}</strong></p>
                    <p>{{ $order->billingAddress->street ?? 'N/A' }}</p>
                    <p>{{ $order->billingAddress->city ?? 'N/A' }}, {{ $order->billingAddress->country ?? 'N/A' }}</p>
                    @if($order->billingAddress->phone_number)
                        <p class="phone">📞 {{ $order->billingAddress->phone_number }}</p>
                    @endif
                @elseif($order->shippingAddress)
                    <p><strong>{{ $order->shippingAddress->name ?? $order->user->first_name . ' ' . $order->user->last_name }}</strong></p>
                    <p>{{ $order->shippingAddress->street ?? 'N/A' }}</p>
                    <p>{{ $order->shippingAddress->city ?? 'N/A' }}, {{ $order->shippingAddress->country ?? 'N/A' }}</p>
                    @if($order->shippingAddress->phone_number)
                        <p class="phone">📞 {{ $order->shippingAddress->phone_number }}</p>
                    @endif
                @else
                    <p><strong>{{ $order->user->first_name }} {{ $order->user->last_name }}</strong></p>
                    <p>{{ $order->user->email }}</p>
                    @if($order->user->phone_number)
                        <p class="phone">📞 {{ $order->user->phone_number }}</p>
                    @endif
                @endif
            </div>

            <!-- Ship To -->
            <div class="address-card">
                <h3>SHIP TO</h3>
                @if($order->shippingAddress)
                    <p><strong>{{ $order->shippingAddress->name ?? $order->user->first_name . ' ' . $order->user->last_name }}</strong></p>
                    <p>{{ $order->shippingAddress->street ?? 'N/A' }}</p>
                    <p>{{ $order->shippingAddress->city ?? 'N/A' }}, {{ $order->shippingAddress->country ?? 'N/A' }}</p>
                    @if($order->shippingAddress->phone_number)
                        <p class="phone">📞 {{ $order->shippingAddress->phone_number }}</p>
                    @endif
                @else
                    <p><strong>{{ $order->user->first_name }} {{ $order->user->last_name }}</strong></p>
                    <p>{{ $order->user->email }}</p>
                    @if($order->user->phone_number)
                        <p class="phone">📞 {{ $order->user->phone_number }}</p>
                    @endif
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Variation</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $item)
                    <tr>
                        <td>
                            <span class="product-title">{{ $item->title ?? 'Product' }}</span>
                        </td>
                        <td>
                            @if(!empty($item->selected_variation))
                                @php $attrs = json_decode($item->selected_variation, true); @endphp
                                @if(is_array($attrs))
                                    @foreach($attrs as $key => $val)
                                        <span class="variation-tag">{{ ucfirst($key) }}: {{ $val }}</span>
                                    @endforeach
                                @else
                                    -
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">{{ $item->quantity ?? 0 }}</td>
                        <td class="text-right">{{ $currency }} {{ number_format($item->price ?? 0, 2) }}</td>
                        <td class="text-right">{{ $currency }} {{ number_format(($item->price ?? 0) * ($item->quantity ?? 0), 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">
                            No items found in this order
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-card">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span class="amount">{{ $currency }} {{ number_format($order->total ?? 0, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>{{ $currency }} {{ number_format($order->shipping_cost ?? 0, 2) }}</span>
                </div>
                <div class="total-row">
                    <span>Tax:</span>
                    <span>{{ $currency }} {{ number_format($order->tax_cost ?? 0, 2) }}</span>
                </div>
                @if($order->totalRefunded && $order->totalRefunded() > 0)
                <div class="total-row" style="color: #dc2626;">
                    <span>Refunded:</span>
                    <span>-{{ $currency }} {{ number_format($order->totalRefunded(), 2) }}</span>
                </div>
                @endif
                <div class="total-row grand-total">
                    <span>Grand Total:</span>
                    <span class="amount">{{ $currency }} {{ number_format($order->total_amount ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>{{ $settings->website ?? config('app.url') }}</p>
            <p>{{ $settings->email ?? 'support@example.com' }} | {{ $settings->phone ?? '+1 234 567 890' }}</p>
            @if($settings && $settings->footer_note)
                <div class="footer-note">{{ $settings->footer_note }}</div>
            @else
                <div class="footer-note">Thank you for your business!</div>
            @endif
        </div>
    </div>
</body>
</html>
