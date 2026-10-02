@extends('layouts.master')

@section('title', 'Order Management')

@section('content')
@php $cur = \App\Support\Money::symbol(); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Orders" icon="ri-shopping-cart-2-line" subtitle="Every GozakMart order, updated live as customers check out.">
        <x-slot:pills>
            <span class="cb-meta-pill" id="unattendedBadge" style="{{ $stats['pending'] ? '' : 'display:none;' }}">
                <i class="ri-alarm-warning-line"></i> <span id="unattendedCount">{{ $stats['pending'] }}</span> pending orders
            </span>
            <span class="cb-meta-pill"><i class="ri-checkbox-circle-line"></i> {{ number_format($stats['paid']) }} paid</span>
            <span class="cb-meta-pill"><i class="ri-time-line"></i> {{ number_format($stats['unpaid']) }} not paid</span>
        </x-slot:pills>
        <x-slot:actions>
            <button type="button" class="cb-hero-btn" onclick="exportOrders('xlsx')"><i class="ri-file-excel-2-line"></i> Export Excel</button>
            <button type="button" class="cb-hero-btn" onclick="exportOrders('csv')"><i class="ri-file-text-line"></i> Export CSV</button>
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Total revenue (paid)" :value="$cur . number_format($analytics['total_revenue'], 2)" icon="ri-money-dollar-circle-line" accent="green" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Total orders" :value="number_format($stats['total'])" icon="ri-shopping-bag-3-line" accent="sky" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Revenue growth (month on month)" :value="($analytics['revenue_growth'] >= 0 ? '+' : '') . $analytics['revenue_growth'] . '%'" icon="ri-line-chart-line" :accent="$analytics['revenue_growth'] >= 0 ? 'teal' : 'rose'" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Average order value" :value="$cur . number_format($analytics['avg_order_value'], 2)" icon="ri-receipt-line" accent="amber" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <x-cb.card title="Revenue — last 30 days" icon="ri-line-chart-line">
                <div style="height:300px;"><canvas id="salesChart"></canvas></div>
            </x-cb.card>
        </div>
        <div class="col-xl-4">
            <x-cb.card title="Top sellers — last 30 days" icon="ri-fire-line">
                @forelse($analytics['top_products'] as $item)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-truncate me-2">{{ $item->product?->title ?? 'Unknown product' }}</span>
                        <span class="badge bg-primary rounded-pill">{{ $item->total_sold }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No sales data.</p>
                @endforelse
            </x-cb.card>
            <x-cb.card title="Status distribution" icon="ri-pie-chart-line">
                <div style="height:200px;"><canvas id="statusChart"></canvas></div>
            </x-cb.card>
        </div>
    </div>

    <x-cb.card title="All Orders" icon="ri-list-check" :flush="true">
        <x-slot:tools>
            <select id="f-status" class="form-select form-select-sm" data-dt-filter="#ordersTable" style="width:auto;">
                <option value="">All statuses</option>
                @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }} ({{ $stats[$s] ?? 0 }})</option>
                @endforeach
            </select>
            <select id="f-payment" class="form-select form-select-sm" data-dt-filter="#ordersTable" style="width:auto;">
                <option value="">Any payment</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
                <option value="pending">Pending</option>
            </select>
            <input type="date" id="f-from" class="form-control form-control-sm" data-dt-filter="#ordersTable" style="width:auto;" title="From">
            <input type="date" id="f-to" class="form-control form-control-sm" data-dt-filter="#ordersTable" style="width:auto;" title="To">
        </x-slot:tools>
        <div class="p-3 gz-dt-wrap">
            <table id="ordersTable" class="table gz-dt align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Customer</th>
                        <th>Date &amp; Time</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th style="width:60px;">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-cb.card>

</div>
</div>

<!-- Audio for live notifications -->
<audio id="statusChangeSound" preload="auto"><source src="{{ asset('sounds/notification-ding.mp3') }}" type="audio/mpeg"></audio>
<audio id="newOrderSound" preload="auto"><source src="{{ asset('sounds/cash-register.mp3') }}" type="audio/mpeg"></audio>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/laravel-echo@1.15.3/dist/echo.iife.js"></script>
<script src="https://js.pusher.com/8.2/pusher.min.js"></script>
<script>
var CUR = @json($cur);
var ORDER_URLS = {
    data:   @json(route('adminorders.data')),
    status: @json(route('adminorders.status', '__ID__')),
    email:  @json(route('adminorders.emailInvoice', '__ID__')),
    export: @json(route('adminorders.export'))
};
var unattendedCount = {{ (int) $stats['pending'] }};
var ordersTable;

function updateUnattendedBadge() {
    $('#unattendedCount').text(unattendedCount);
    $('#unattendedBadge').toggle(unattendedCount > 0);
}

function currentFilters() {
    return { status: $('#f-status').val(), payment_status: $('#f-payment').val(), from: $('#f-from').val(), to: $('#f-to').val() };
}

function exportOrders(format) {
    var url = new URL(ORDER_URLS.export);
    var f = currentFilters();
    Object.keys(f).forEach(function (k) { if (f[k]) url.searchParams.append(k, f[k]); });
    url.searchParams.append('format', format);
    window.location = url;
}

function playSound(id) { var a = document.getElementById(id); if (a) { a.currentTime = 0; a.play().catch(function () {}); } }

document.addEventListener('DOMContentLoaded', function () {
    ordersTable = GZ.dt('#ordersTable', {
        url: ORDER_URLS.data,
        order: [[2, 'desc']],
        filters: currentFilters,
        columns: [
            { data: 'invoice',        name: 'invoice' },
            { data: 'customer',       name: 'customer' },
            { data: 'created_at',     name: 'orders.created_at', searchable: false },
            { data: 'total_amount',   name: 'orders.total_amount', searchable: false },
            { data: 'payment_status', name: 'orders.payment_status', searchable: false },
            { data: 'status',         name: 'orders.status', searchable: false },
            { data: 'items_count',    name: 'items_count', searchable: false },
            { data: 'action',         name: 'action', orderable: false, searchable: false }
        ]
    });

    // Status change (delegated — works on every page of the table)
    $('#ordersTable').on('change', '.status-select', function () {
        var sel = this, row = $(sel).closest('tr'), oldStatus = sel.dataset.current, newStatus = sel.value;
        $.post(ORDER_URLS.status.replace('__ID__', sel.dataset.id), { status: newStatus })
            .done(function () {
                sel.dataset.current = newStatus;
                row.removeClass('table-warning table-success')
                   .addClass(newStatus === 'delivered' ? 'table-success' : (newStatus === 'pending' ? 'table-warning' : ''));
                if (oldStatus === 'pending' && newStatus !== 'pending') { unattendedCount = Math.max(0, unattendedCount - 1); }
                if (oldStatus !== 'pending' && newStatus === 'pending') { unattendedCount++; }
                updateUnattendedBadge();
                GZ.toast('Status updated to ' + newStatus);
            })
            .fail(function (x) { sel.value = oldStatus; Swal.fire('Error', GZ.xhrError(x), 'error'); });
    });

    $('#ordersTable').on('click', '.email-invoice', function () {
        $.post(ORDER_URLS.email.replace('__ID__', $(this).data('id')))
            .done(function () { Swal.fire('Success', 'Invoice sent to customer', 'success'); })
            .fail(function () { Swal.fire('Error', 'Failed to send invoice', 'error'); });
    });

    // Charts
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: { labels: @json($analytics['sales_chart']['labels'] ?? []), datasets: [{ label: 'Daily sales', data: @json($analytics['sales_chart']['data'] ?? []), borderColor: '#0d9488', backgroundColor: 'rgba(13,148,136,.12)', tension: .4, fill: true }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: function (v) { return CUR + v; } } } } }
    });
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
            datasets: [{ data: [{{ $stats['pending'] ?? 0 }}, {{ $stats['processing'] ?? 0 }}, {{ $stats['shipped'] ?? 0 }}, {{ $stats['delivered'] ?? 0 }}, {{ $stats['cancelled'] ?? 0 }}], backgroundColor: ['#f59e0b', '#0ea5e9', '#6366f1', '#22c55e', '#f43f5e'] }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // Live updates (Pusher) — reload the table instead of hand-building rows
    @if(config('broadcasting.connections.pusher.key') || env('PUSHER_APP_KEY'))
    try {
        window.Pusher = Pusher;
        window.Echo = new Echo({ broadcaster: 'pusher', key: @json(env('PUSHER_APP_KEY')), cluster: @json(env('PUSHER_APP_CLUSTER')), forceTLS: true });
        Echo.private('orders')
            .listen('OrderStatusChanged', function (e) {
                playSound('statusChangeSound');
                ordersTable.ajax.reload(null, false);
                GZ.toast('Order #' + e.invoice_number + ' updated to ' + e.new_status, 'info');
            })
            .listen('NewOrderCreated', function (e) {
                playSound('newOrderSound');
                unattendedCount++; updateUnattendedBadge();
                ordersTable.ajax.reload(null, false);
                GZ.toast('New order #' + e.invoice_number + ' — ' + CUR + e.total);
            });
    } catch (err) { console.warn('Live order updates unavailable', err); }
    @endif
});
</script>

@endsection
