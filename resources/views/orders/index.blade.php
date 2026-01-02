{{-- resources/views/orders/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Order Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Order Management</h4>
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Ecommerce</a></li>
                            <li class="breadcrumb-item active">Orders</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Date Range Selector -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" id="dateFrom" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" id="dateTo" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-outline-primary" onclick="setPreset('today')">Today</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setPreset('7days')">Last 7 Days</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setPreset('30days')">Last 30 Days</button>
                                        <button type="button" class="btn btn-outline-primary active" onclick="setPreset('this_month')">This Month</button>
                                        <button type="button" class="btn btn-outline-primary" onclick="setPreset('last_month')">Last Month</button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button id="applyRange" class="btn btn-primary w-100">Apply Range</button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Current period: <strong id="periodDisplay">This Month</strong></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Cards -->
            <div class="row" id="analyticsCards">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-primary-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-primary mb-0">Total Revenue</p>
                                    <h4 class="fs-22 fw-semibold mb-0">${{ number_format($analytics['total_revenue'], 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary rounded-circle fs-3">
                                        <i class="bi bi-currency-dollar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-success-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-success mb-0">Total Orders</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($stats['total']) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success rounded-circle fs-3">
                                        <i class="bi bi-cart-check"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border-0 {{ $analytics['revenue_growth'] >= 0 ? 'bg-info-subtle' : 'bg-danger-subtle' }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium {{ $analytics['revenue_growth'] >= 0 ? 'text-info' : 'text-danger' }} mb-0">Revenue Growth</p>
                                    <h4 class="fs-22 fw-semibold mb-0">
                                        {{ $analytics['revenue_growth'] >= 0 ? '+' : '' }}{{ $analytics['revenue_growth'] }}%
                                    </h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title {{ $analytics['revenue_growth'] >= 0 ? 'bg-info' : 'bg-danger' }} rounded-circle fs-3">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-warning-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-warning mb-0">Avg Order Value</p>
                                    <h4 class="fs-22 fw-semibold mb-0">${{ number_format($analytics['avg_order_value'], 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3">
                                        <i class="bi bi-receipt"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Top Products -->
            <div class="row mt-4">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Revenue Overview</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Selling Products</h5>
                        </div>
                        <div class="card-body">
                            @if($analytics['top_products']->count() > 0)
                                <ul class="list-group list-group-flush">
                                    @foreach($analytics['top_products'] as $item)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span class="text-truncate" style="max-width: 180px;">
                                                {{ $item->product?->name ?? 'Unknown Product' }}
                                            </span>
                                            <span class="badge bg-primary rounded-pill">{{ $item->total_sold }} sold</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted text-center py-4">No sales in selected period</p>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Order Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters + Orders Table -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Orders <span class="badge bg-dark-subtle text-dark ms-1">{{ $orders->total() }}</span></h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" onclick="exportOrders('xlsx')">Export Excel</button>
                                <button type="button" class="btn btn-info" onclick="exportOrders('csv')">Export CSV</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('adminorders.index') }}" method="GET" class="row g-3 align-items-end mb-4">
                                <div class="col-md-3">
                                    <input type="text" name="search" class="form-control" placeholder="Search Invoice / Customer..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <select name="status" class="form-select">
                                        <option value="">All Status</option>
                                        @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="payment_status" class="form-select">
                                        <option value="">Payment Status</option>
                                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0">
                                    <thead class="table-active">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                            <th>Status</th>
                                            <th>Items</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                        <tr>
                                            <td>
                                                <a href="{{ route('adminorders.show', $order) }}" class="fw-bold text-primary">
                                                    {{ $order->invoice_number ?? substr($order->id, 0, 8) }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3">
                                                        <div class="avatar-title bg-secondary-subtle rounded-circle text-uppercase">
                                                            {{ Str::substr($order->user->first_name, 0, 1) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $order->user->first_name }} {{ $order->user->last_name }}</h6>
                                                        <small class="text-muted">{{ $order->user->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $order->created_at->format('d M, Y') }}</td>
                                            <td class="fw-bold text-success">${{ number_format($order->total_amount, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $order->payment_status == 'paid' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                    {{ ucfirst($order->payment_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm status-select" data-id="{{ $order->id }}">
                                                    @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                                        <option value="{{ $s }}" {{ $order->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-center">{{ $order->items_count }}</td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="{{ route('adminorders.show', $order) }}">View Details</a></li>
                                                        <li><a class="dropdown-item" href="{{ route('adminorders.invoice', $order) }}" target="_blank">PDF Invoice</a></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="emailInvoice('{{ $order->id }}')">Email Invoice</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">No orders found.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-4 align-items-center">
                                <div class="col-sm">
                                    <div class="text-muted text-center text-sm-start">
                                        Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} entries
                                    </div>
                                </div>
                                <div class="col-sm-auto">
                                    {!! $orders->appends(request()->query())->links('pagination::bootstrap-5') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let salesChart = null;
let statusChart = null;

function setPreset(period) {
    const from = document.getElementById('dateFrom');
    const to = document.getElementById('dateTo');
    const display = document.getElementById('periodDisplay');
    const today = new Date();
    to.value = today.toISOString().split('T')[0];

    switch(period) {
        case 'today':
            from.value = to.value;
            display.textContent = 'Today';
            break;
        case '7days':
            from.value = new Date(today.setDate(today.getDate() - 6)).toISOString().split('T')[0];
            display.textContent = 'Last 7 Days';
            break;
        case '30days':
            from.value = new Date(today.setDate(today.getDate() - 29)).toISOString().split('T')[0];
            display.textContent = 'Last 30 Days';
            break;
        case 'this_month':
            from.value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            display.textContent = 'This Month';
            break;
        case 'last_month':
            const last = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            from.value = last.toISOString().split('T')[0];
            to.value = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
            display.textContent = 'Last Month';
            break;
    }
}

function updateAnalytics() {
    const from = document.getElementById('dateFrom').value;
    const to = document.getElementById('dateTo').value;

    if (!from || !to) return;

    axios.get('{{ route("adminorders.index") }}', {
        params: { from, to, analytics_only: 1 }
    })
    .then(res => {
        // Update cards
        document.getElementById('analyticsCards').innerHTML = res.data.cards;

        // Update charts and top products
        const chartsContainer = document.querySelector('#chartsSection') || document.querySelector('.row.mt-4:nth-of-type(2)');
        chartsContainer.innerHTML = res.data.charts;

        // Re-init charts
        initCharts(res.data.chart_data);
    })
    .catch(() => Swal.fire('Error', 'Failed to update analytics', 'error'));
}

function initCharts(data) {
    if (salesChart) salesChart.destroy();
    if (statusChart) statusChart.destroy();

    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: data.sales_labels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: data.sales_data,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => '$' + v } } }
            }
        });
    }

    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    data: data.status_counts,
                    backgroundColor: ['#ffc107', '#0dcaf0', '#0d6efd', '#198754', '#dc3545']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
}

document.getElementById('applyRange').addEventListener('click', updateAnalytics);

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    setPreset('this_month');
    initCharts(@json([
        'sales_labels' => $analytics['sales_chart']['labels'],
        'sales_data' => $analytics['sales_chart']['data'],
        'status_counts' => [$stats['pending'], $stats['processing'], $stats['shipped'], $stats['delivered'], $stats['cancelled']]
    ]));
});

// Existing functionality
document.querySelectorAll('.status-select').forEach(el => {
    el.addEventListener('change', function () {
        axios.post('{{ route("adminorders.status", ":id") }}'.replace(':id', this.dataset.id), { status: this.value })
            .then(() => Swal.fire('Success', 'Status updated', 'success'))
            .catch(() => this.value = this.dataset.previousValue || 'pending');
        this.dataset.previousValue = this.value;
    });
});

window.exportOrders = format => {
    const url = new URL('{{ route("adminorders.export") }}');
    new URLSearchParams(window.location.search).forEach((v, k) => url.searchParams.append(k, v));
    url.searchParams.append('format', format);
    window.location = url;
};

window.emailInvoice = id => {
    axios.post('{{ route("adminorders.emailInvoice", ":id") }}'.replace(':id', id))
        .then(() => Swal.fire('Sent!', 'Invoice emailed', 'success'))
        .catch(() => Swal.fire('Error', 'Failed to send', 'error'));
};
</script>
@endsection
