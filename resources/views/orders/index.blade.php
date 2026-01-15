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

            <!-- Analytics Cards -->
            <div class="row">
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
                            <h5 class="card-title mb-0">Revenue Overview (Last 30 Days)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Selling Products (Last 30 Days)</h5>
                        </div>
                        <div class="card-body">
                            @if($analytics['top_products']->count() > 0)
                                <ul class="list-group list-group-flush">
                                    @foreach($analytics['top_products'] as $item)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $item->product?->name ?? 'Unknown Product' }}
                                            <span class="badge bg-primary rounded-pill">{{ $item->total_sold }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">No sales data.</p>
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

            <!-- Live Unattended Orders Counter -->
            <div class="row mb-3">
                <div class="col-12 text-end">
                    <span class="badge bg-danger fs-6 px-3 py-2" id="unattendedBadge" style="display: none;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span id="unattendedCount">0</span> Unattended Orders
                    </span>
                </div>
            </div>

            <!-- Filters -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('adminorders.index') }}" method="GET" class="row g-3 align-items-end">
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
                                <div class="col-md-2">
                                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                                </div>
                                <div class="col-md-2">
                                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                                </div>
                                <div class="col-md-1">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">
                                Orders <span class="badge bg-dark-subtle text-dark ms-1">{{ $orders->total() }}</span>
                            </h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" onclick="exportOrders('xlsx')">
                                    Export Excel
                                </button>
                                <button type="button" class="btn btn-info" onclick="exportOrders('csv')">
                                    Export CSV
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0" id="ordersTable">
                                    <thead class="table-active">
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Customer</th>
                                            <th>Date & Time</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                            <th>Status</th>
                                            <th>Items</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                        <tr class="order-row"
                                            data-order-id="{{ $order->id }}"
                                            data-original-status="{{ $order->status }}"
                                            data-has-been-updated="{{ $order->status_updated_at ? 'true' : 'false' }}"
                                            data-is-delivered="{{ $order->status === 'delivered' ? 'true' : 'false' }}">
                                            <td>
                                                <a href="{{ route('adminorders.show', $order) }}" class="fw-bold text-primary">
                                                    {{ $order->invoice_number ?? substr($order->id, 0, 8) }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3">
                                                        <div class="avatar-title bg-secondary-subtle rounded-circle text-uppercase">
                                                            {{ Str::substr($order->user->first_name ?? 'G', 0, 1) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $order->user->first_name ?? 'Guest' }} {{ $order->user->last_name ?? '' }}</h6>
                                                        <small class="text-muted">{{ $order->user->email ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $order->created_at->format('d M, Y H:i') }}</td>
                                            <td class="fw-bold text-success">${{ number_format($order->total_amount, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $order->payment_status == 'paid' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                    {{ ucfirst($order->payment_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm status-select"
                                                        data-id="{{ $order->id }}"
                                                        data-current="{{ $order->status }}">
                                                    @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                                                        <option value="{{ $s }}" {{ $order->status == $s ? 'selected' : '' }}>
                                                            {{ ucfirst($s) }}
                                                        </option>
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

<!-- Audio Elements for Notifications -->
<audio id="statusChangeSound" preload="auto">
    <source src="{{ asset('sounds/notification-ding.mp3') }}" type="audio/mpeg">
</audio>
<audio id="newOrderSound" preload="auto">
    <source src="{{ asset('sounds/cash-register.mp3') }}" type="audio/mpeg">
</audio>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/laravel-echo@1.15.3/dist/echo.iife.js"></script>
<script src="https://unpkg.com/@reverbjs/reverb-js@latest/dist/reverb.iife.js"></script>

<script>
// ================== GLOBAL VARIABLES ==================
let unattendedCount = {{ $orders->whereNull('status_updated_at')->where('status', 'pending')->count() }};

// ================== BADGE UPDATE FUNCTION ==================
function updateUnattendedBadge() {
    const badge = document.getElementById('unattendedBadge');
    const countEl = document.getElementById('unattendedCount');

    if (!badge || !countEl) return;

    countEl.textContent = unattendedCount;
    badge.style.display = unattendedCount > 0 ? 'inline-block' : 'none';
}

// ================== ROW HIGHLIGHT FUNCTION ==================
function applyRowHighlight(row) {
    row.classList.remove('table-warning', 'table-success');

    if (row.dataset.isDelivered === 'true') {
        row.classList.add('table-success');
    } else if (row.dataset.hasBeenUpdated === 'false') {
        row.classList.add('table-warning');
    }
}

// ================== INITIAL SETUP ==================
document.addEventListener('DOMContentLoaded', function () {
    // Apply highlights to existing rows
    document.querySelectorAll('.order-row').forEach(row => applyRowHighlight(row));

    // Update badge
    updateUnattendedBadge();
});

// ================== ECHO + REVERB INITIALIZATION ==================
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: '{{ env('REVERB_APP_KEY') }}',
    wsHost: window.location.hostname,
    wsPort: {{ env('REVERB_PORT', 8080) }},
    wssPort: {{ env('REVERB_PORT', 8080) }},
    forceTLS: window.location.protocol === 'https:',
    enabledTransports: ['ws', 'wss'],
});

// ================== REAL-TIME LISTENERS ==================
Echo.private('orders')
    // Status Change Listener
    .listen('OrderStatusChanged', (e) => {
        const row = document.querySelector(`tr[data-order-id="${e.order_id}"]`);
        if (!row) return;

        // Play sound
        document.getElementById('statusChangeSound').currentTime = 0;
        document.getElementById('statusChangeSound').play().catch(() => {});

        // Update status dropdown
        const select = row.querySelector('.status-select');
        if (select) select.value = e.new_status;

        // Update data attributes
        const wasPending = row.dataset.originalStatus === 'pending';
        row.dataset.originalStatus = e.new_status;
        row.dataset.isDelivered = (e.new_status === 'delivered') ? 'true' : 'false';
        row.dataset.hasBeenUpdated = 'true';

        // Re-apply highlight
        applyRowHighlight(row);

        // Decrease unattended count if it was pending and now changed
        if (wasPending && e.new_status !== 'pending' && row.dataset.hasBeenUpdated === 'false') {
            unattendedCount = Math.max(0, unattendedCount - 1);
            updateUnattendedBadge();
        }

        // Toast notification
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: `Order #${e.invoice_number} updated to ${e.new_status}`,
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
    })

    // New Order Arrival Listener
    .listen('NewOrderCreated', (e) => {
        // Play new order sound
        document.getElementById('newOrderSound').currentTime = 0;
        document.getElementById('newOrderSound').play().catch(() => {});

        // Increase unattended count (new order is unattended)
        unattendedCount++;
        updateUnattendedBadge();

        // Create new row at the top
        const tbody = document.querySelector('#ordersTable tbody');
        const newRow = document.createElement('tr');
        newRow.classList.add('order-row', 'table-warning');
        newRow.dataset.orderId = e.id;
        newRow.dataset.originalStatus = 'pending';
        newRow.dataset.hasBeenUpdated = 'false';
        newRow.dataset.isDelivered = 'false';

        newRow.innerHTML = `
            <td>
                <a href="{{ url('/adminorders') }}/${e.id}" class="fw-bold text-primary">${e.invoice_number}</a>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-xs me-3">
                        <div class="avatar-title bg-secondary-subtle rounded-circle text-uppercase">
                            ${e.customer.charAt(0) || 'G'}
                        </div>
                    </div>
                    <div>
                        <h6 class="mb-0">${e.customer}</h6>
                        <small class="text-muted">New Customer</small>
                    </div>
                </div>
            </td>
            <td>${e.created_at}</td>
            <td class="fw-bold text-success">$${e.total}</td>
            <td>
                <span class="badge bg-danger-subtle text-danger">Unpaid</span>
            </td>
            <td>
                <select class="form-select form-select-sm status-select" data-id="${e.id}" data-current="pending">
                    <option value="pending" selected>Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </td>
            <td class="text-center">0</td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ url('/adminorders') }}/${e.id}">View Details</a></li>
                        <li><a class="dropdown-item" href="{{ url('/adminorders') }}/${e.id}/invoice" target="_blank">PDF Invoice</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="emailInvoice('${e.id}')">Email Invoice</a></li>
                    </ul>
                </div>
            </td>
        `;

        // Insert at the very top
        tbody.insertBefore(newRow, tbody.firstChild);

        // Attach status change listener to the new select
        newRow.querySelector('.status-select').addEventListener('change', statusChangeHandler);

        // Toast notification for new order
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `New Order Received! #${e.invoice_number} - $${e.total}`,
            showConfirmButton: false,
            timer: 6000,
            timerProgressBar: true
        });
    });

// ================== STATUS CHANGE HANDLER ==================
function statusChangeHandler() {
    const orderId = this.dataset.id;
    const newStatus = this.value;
    const row = this.closest('.order-row');
    const oldStatus = row.dataset.originalStatus;

    // Optimistic update
    row.dataset.originalStatus = newStatus;
    row.dataset.isDelivered = newStatus === 'delivered' ? 'true' : 'false';
    row.dataset.hasBeenUpdated = 'true';
    applyRowHighlight(row);

    axios.post(`/adminorders/${orderId}/status`, { status: newStatus })
        .then(() => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Status Updated',
                showConfirmButton: false,
                timer: 2000
            });
        })
        .catch(() => {
            Swal.fire('Error', 'Failed to update status', 'error');
            this.value = oldStatus;
            row.dataset.originalStatus = oldStatus;
            row.dataset.isDelivered = oldStatus === 'delivered' ? 'true' : 'false';
            row.dataset.hasBeenUpdated = row.dataset.hasBeenUpdated || 'false';
            applyRowHighlight(row);
        });
}

// Attach handler to all status selects
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', statusChangeHandler);
});

// ================== EXPORT FUNCTION ==================
function exportOrders(format) {
    const url = new URL('{{ route("adminorders.export") }}');
    new URLSearchParams(window.location.search).forEach((v, k) => url.searchParams.append(k, v));
    url.searchParams.append('format', format);
    window.location = url;
}

// ================== EMAIL INVOICE FUNCTION ==================
function emailInvoice(id) {
    axios.post('{{ route("adminorders.emailInvoice", ":id") }}'.replace(':id', id))
        .then(() => Swal.fire('Success', 'Invoice sent to customer', 'success'))
        .catch(() => Swal.fire('Error', 'Failed to send invoice', 'error'));
}

// ================== CHART INITIALIZATION ==================
document.addEventListener('DOMContentLoaded', function () {
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: @json($analytics['sales_chart']['labels'] ?? []),
            datasets: [{
                label: 'Daily Sales ($)',
                data: @json($analytics['sales_chart']['data'] ?? []),
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

    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
            datasets: [{
                data: [
                    {{ $stats['pending'] ?? 0 }},
                    {{ $stats['processing'] ?? 0 }},
                    {{ $stats['shipped'] ?? 0 }},
                    {{ $stats['delivered'] ?? 0 }},
                    {{ $stats['cancelled'] ?? 0 }}
                ],
                backgroundColor: ['#ffc107', '#0dcaf0', '#0d6efd', '#198754', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
@endsection
