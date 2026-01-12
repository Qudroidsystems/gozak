<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commissions Report - {{ $settings->store_name ?? 'Store' }}</title>
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
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .store-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .store-details {
            font-size: 11px;
            color: #666;
            margin-bottom: 10px;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
            color: #2c3e50;
        }
        .summary-cards {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .summary-card {
            flex: 1;
            min-width: 200px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 5px;
            text-align: center;
        }
        .summary-card h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .summary-card .value {
            font-size: 20px;
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
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #dee2e6;
            font-size: 11px;
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
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-paid {
            background-color: #28a745;
            color: white;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="store-info">
            @if($settings->logo_url)
                <img src="{{ $settings->logo_url }}" alt="Logo" style="max-height: 60px; margin-bottom: 10px;">
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
        COMMISSIONS REPORT
    </div>

    <!-- Report Info -->
    <div style="background: #f
