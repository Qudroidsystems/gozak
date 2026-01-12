<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $user->name ?? $user->first_name . ' ' . $user->last_name }} - Sales Report</title>
    <style>
        @page {
            margin: 20px;
            size: A4 landscape;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .store-info {
            text-align: center;
            margin-bottom: 15px;
        }
        .store-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .store-details {
            font-size: 10px;
            color: #666;
            margin-bottom: 10px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
            color: #2c3e50;
        }
        .user-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .summary-card {
            flex: 1;
            min-width: 180px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 12px;
            margin: 5px;
            text-align: center;
        }
        .summary-card h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #666;
        }
        .summary-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #2c3e50;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-row {
            background-color: #2c3e50 !important;
            color: white;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-completed {
            background-color: #28a745;
            color: white;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-cash {
            background-color: #28a745;
            color: white;
        }
        .badge-card {
            background-color: #007bff;
            color: white;
        }
        .badge-transfer {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-pos {
            background-color: #dc3545;
            color: white;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .page-break {
            page-break-before: always;
        }
        .mb-20 {
            margin-bottom: 20px;
        }
        .mt-20 {
            margin-top: 20px;
        }
        .signature-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #dee2e6;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="store-info">
            @if($settings->logo_url)
                <img src="{{ $settings->logo_url }}" alt="Logo" style="max-height: 50px; margin-bottom: 8px;">
            @endif
            <div class="store-name">{{ $settings->store_name ?? 'Store Management System' }}</div>
            <div class="store-details">
                @if($settings->address)
                    {{ $settings->address }}<br>
                @endif
                @if($settings->phone)
                    Phone: {{ $settings->phone }} |
                @endif
                @if($settings->email)
                    Email: {{ $settings->email }}<br>
                @endif
                @if($settings->website)
                    Website: {{ $settings->website }}
                @endif
            </div>
        </div>
    </div>

    <!-- Report Title -->
    <div class="report-title">
        SALES PERFORMANCE REPORT - {{ strtoupper($user->name ?? $user->first_name . ' ' . $user->last_name) }}
    </div>

    <!-- User Information -->
    <div class="user-info">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <strong>Sales Person:</strong> {{ $user->name ?? $user->first_name . ' ' . $user->last_name }}<br>
                <strong>Email:</strong> {{ $user->email }}<br>
                <strong>Phone:</strong> {{ $user->phone ?? 'N/A' }}
            </div>
            <div style="text-align: right;">
                <strong>Report Period:</strong> {{ date('M d, Y', strtotime($dateFrom)) }} - {{ date('M d, Y', strtotime($dateTo)) }}<br>
                <strong>Generated Date:</strong> {{ $generatedAt }}<br>
                <strong>Generated By:</strong> {{ $generatedBy }}
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    @php
        $totalRevenue = $sales->sum('total_amount');
        $totalCommission = $sales->sum('commission_amount');
        $totalOrders = $sales->count();
        $averageOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Payment method breakdown
        $cashSales = $sales->where('payment_method', 'cash')->sum('total_amount');
        $cardSales = $sales->where('payment_method', 'card')->sum('total_amount');
        $transferSales = $sales->where('payment_method', 'transfer')->sum('total_amount');
        $posSales = $sales->where('payment_method', 'pos')->sum('total_amount');

        $cashCount = $sales->where('payment_method', 'cash')->count();
        $cardCount = $sales->where('payment_method', 'card')->count();
        $transferCount = $sales->where('payment_method', 'transfer')->count();
        $posCount = $sales->where('payment_method', 'pos')->count();
    @endphp

    <div class="summary-cards">
        <div class="summary-card">
            <h4>TOTAL REVENUE</h4>
            <div class="value">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="summary-card">
            <h4>TOTAL ORDERS</h4>
            <div class="value">{{ number_format($totalOrders) }}</div>
        </div>
        <div class="summary-card">
            <h4>AVERAGE ORDER</h4>
            <div class="value">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($averageOrder, 2) }}</div>
        </div>
        <div class="summary-card">
            <h4>TOTAL COMMISSION</h4>
            <div class="value">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($totalCommission, 2) }}</div>
        </div>
    </div>

    <!-- Payment Method Breakdown -->
    <div class="mb-20">
        <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 5px; margin-bottom: 15px;">
            Payment Method Breakdown
        </h3>
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 180px; background: #f8f9fa; padding: 8px; margin: 3px; border-radius: 5px;">
                <strong>Cash:</strong> {{ $settings->currency_symbol ?? '₦' }}{{ number_format($cashSales, 2) }}<br>
                <small>{{ $cashCount }} orders ({{ $totalOrders > 0 ? number_format(($cashCount/$totalOrders)*100, 1) : 0 }}%)</small>
            </div>
            <div style="flex: 1; min-width: 180px; background: #f8f9fa; padding: 8px; margin: 3px; border-radius: 5px;">
                <strong>Card:</strong> {{ $settings->currency_symbol ?? '₦' }}{{ number_format($cardSales, 2) }}<br>
                <small>{{ $cardCount }} orders ({{ $totalOrders > 0 ? number_format(($cardCount/$totalOrders)*100, 1) : 0 }}%)</small>
            </div>
            <div style="flex: 1; min-width: 180px; background: #f8f9fa; padding: 8px; margin: 3px; border-radius: 5px;">
                <strong>Transfer:</strong> {{ $settings->currency_symbol ?? '₦' }}{{ number_format($transferSales, 2) }}<br>
                <small>{{ $transferCount }} orders ({{ $totalOrders > 0 ? number_format(($transferCount/$totalOrders)*100, 1) : 0 }}%)</small>
            </div>
            <div style="flex: 1; min-width: 180px; background: #f8f9fa; padding: 8px; margin: 3px; border-radius: 5px;">
                <strong>POS:</strong> {{ $settings->currency_symbol ?? '₦' }}{{ number_format($posSales, 2) }}<br>
                <small>{{ $posCount }} orders ({{ $totalOrders > 0 ? number_format(($posCount/$totalOrders)*100, 1) : 0 }}%)</small>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 5px; margin-bottom: 15px;">
        Detailed Sales Records
    </h3>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th class="text-center">Items</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Commission</th>
                <th>Payment Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            @php
                $itemCount = $sale->items ? $sale->items->count() : 0;
                $paymentBadgeClass = 'badge-' . $sale->payment_method;
                $statusBadgeClass = 'badge-' . $sale->status;
            @endphp
            <tr>
                <td>#{{ $sale->id }}</td>
                <td>{{ $sale->order_date->format('M d, Y') }}</td>
                <td>{{ $sale->customer->name ?? 'Guest' }}</td>
                <td class="text-center">{{ $itemCount }}</td>
                <td class="text-right">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($sale->total_amount, 2) }}</td>
                <td class="text-right">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($sale->commission_amount ?? 0, 2) }}</td>
                <td>
                    <span class="badge {{ $paymentBadgeClass }}">
                        {{ strtoupper($sale->payment_method) }}
                    </span>
                </td>
                <td>
                    <span class="badge {{ $statusBadgeClass }}">
                        {{ strtoupper($sale->status) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ $settings->currency_symbol ?? '₦' }}{{ number_format($totalRevenue, 2) }}</strong></td>
                <td class="text-right"><strong>{{ $settings->currency_symbol ?? '₦' }}{{ number_format($totalCommission, 2) }}</strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Daily Performance Summary -->
    @php
        $salesByDate = [];
        foreach($sales as $sale) {
            $date = $sale->order_date->format('Y-m-d');
            if(!isset($salesByDate[$date])) {
                $salesByDate[$date] = [
                    'count' => 0,
                    'revenue' => 0,
                    'commission' => 0
                ];
            }
            $salesByDate[$date]['count']++;
            $salesByDate[$date]['revenue'] += $sale->total_amount;
            $salesByDate[$date]['commission'] += $sale->commission_amount ?? 0;
        }
        ksort($salesByDate);
    @endphp

    @if(count($salesByDate) > 1)
    <div class="page-break"></div>
    <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 5px; margin-bottom: 15px; margin-top: 30px;">
        Daily Performance Summary
    </h3>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th class="text-center">Orders</th>
                <th class="text-right">Revenue</th>
                <th class="text-right">Commission</th>
                <th class="text-right">Avg. Order</th>
                <th class="text-right">% of Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($salesByDate as $date => $data)
            <tr>
                <td>{{ date('M d, Y', strtotime($date)) }}</td>
                <td class="text-center">{{ $data['count'] }}</td>
                <td class="text-right">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($data['revenue'], 2) }}</td>
                <td class="text-right">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($data['commission'], 2) }}</td>
                <td class="text-right">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($data['count'] > 0 ? $data['revenue'] / $data['count'] : 0, 2) }}</td>
                <td class="text-right">{{ $totalRevenue > 0 ? number_format(($data['revenue'] / $totalRevenue) * 100, 1) : 0 }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td class="text-center"><strong>{{ $totalOrders }}</strong></td>
                <td class="text-right"><strong>{{ $settings->currency_symbol ?? '₦' }}{{ number_format($totalRevenue, 2) }}</strong></td>
                <td class="text-right"><strong>{{ $settings->currency_symbol ?? '₦' }}{{ number_format($totalCommission, 2) }}</strong></td>
                <td class="text-right"><strong>{{ $settings->currency_symbol ?? '₦' }}{{ number_format($averageOrder, 2) }}</strong></td>
                <td class="text-right"><strong>100%</strong></td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- Top Products Summary -->
    @php
        $productsSummary = [];
        foreach($sales as $sale) {
            if($sale->items) {
                foreach($sale->items as $item) {
                    $productName = $item->product ? ($item->product->name ?? $item->product->title) : 'Product #' . $item->product_id;
                    if(!isset($productsSummary[$productName])) {
                        $productsSummary[$productName] = [
                            'quantity' => 0,
                            'revenue' => 0
                        ];
                    }
                    $productsSummary[$productName]['quantity'] += $item->quantity;
                    $productsSummary[$productName]['revenue'] += ($item->quantity * $item->price);
                }
            }
        }
        arsort($productsSummary);
        $topProducts = array_slice($productsSummary, 0, 10, true);
    @endphp

    @if(count($topProducts) > 0)
    <div class="page-break"></div>
    <h3 style="color: #2c3e50; border-bottom: 2px solid #2c3e50; padding-bottom: 5px; margin-bottom: 15px; margin-top: 30px;">
        Top Selling Products
    </h3>

    <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th class="text-center">Quantity Sold</th>
                <th class="text-right">Revenue</th>
                <th class="text-right">% of Revenue</th>
                <th class="text-right">Avg. Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($topProducts as $productName => $data)
            <tr>
                <td>{{ $productName }}</td>
                <td class="text-center">{{ number_format($data['quantity']) }}</td>
                <td class="text-right">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($data['revenue'], 2) }}</td>
                <td class="text-right">{{ $totalRevenue > 0 ? number_format(($data['revenue'] / $totalRevenue) * 100, 1) : 0 }}%</td>
                <td class="text-right">{{ $settings->currency_symbol ?? '₦' }}{{ number_format($data['quantity'] > 0 ? $data['revenue'] / $data['quantity'] : 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Footer & Signature -->
    <div class="footer mt-20">
        {{ $settings->footer_note ?? 'Confidential - For Internal Use Only' }}<br>
        Report generated electronically by {{ $settings->store_name ?? 'Store Management System' }}<br>
        Page <span class="page-number"></span> of <span class="page-count"></span>
    </div>

    <div class="signature-section">
        <div style="display: flex; justify-content: space-between; margin-top: 40px;">
            <div style="text-align: center;">
                <div class="signature-line"></div>
                <p style="margin-top: 5px; font-size: 10px;">
                    <strong>{{ $user->name ?? $user->first_name . ' ' . $user->last_name }}</strong><br>
                    Sales Person
                </p>
            </div>
            <div style="text-align: center;">
                <div class="signature-line"></div>
                <p style="margin-top: 5px; font-size: 10px;">
                    <strong>Authorized Signature</strong><br>
                    {{ $settings->store_name ?? 'Management' }}
                </p>
            </div>
        </div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 9;
            $font = $fontMetrics->getFont("helvetica");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 25;
            $pdf->page_text($x, $y, $text, $font, $size);

            // Add report info on left
            $reportText = "User Sales Report - {{ $user->name ?? $user->first_name . ' ' . $user->last_name }}";
            $pdf->page_text(20, $y, $reportText, $font, $size);

            // Add date on right
            $dateText = "{{ date('Y-m-d H:i:s') }}";
            $dateWidth = $fontMetrics->get_text_width($dateText, $font, $size);
            $pdf->page_text($pdf->get_width() - $dateWidth - 20, $y, $dateText, $font, $size);
        }
    </script>
</body>
</html>
