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
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Order Management' }}</h4>
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Ecommerce</a></li>
                            <li class="breadcrumb-item active">Orders</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-primary-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-primary mb-0">Total Orders</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($stats['total']) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary rounded-circle fs-3">
                                        <i class="bi bi-cart-check"></i>
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
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-success mb-0">Total Revenue</p>
                                    <h4 class="fs-22 fw-semibold mb-0">${{ number_format($analytics['total_revenue'] ?? 0, 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success rounded-circle fs-3">
                                        <i class="bi bi-currency-dollar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-info-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-info mb-0">Pending Orders</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $stats['pending'] }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info rounded-circle fs-3">
                                        <i class="bi bi-hourglass-split"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate {{ $stats['low_stock_count'] > 0 ? 'bg-danger-subtle' : 'bg-secondary-subtle' }} border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium {{ $stats['low_stock_count'] > 0 ? 'text-danger' : 'text-secondary' }} mb-0">Low Stock Alert</p>
                                    <h4 class="fs-22 fw-semibold mb-0 {{ $stats['low_stock_count'] > 0 ? 'text-danger' : 'text-secondary' }}">{{ $stats['low_stock_count'] ?? 0 }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title {{ $stats['low_stock_count'] > 0 ? 'bg-danger' : 'bg-secondary' }} rounded-circle fs-3">
                                        <i class="bi bi-exclamation-triangle-fill text-white"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mt-4">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Sales Overview (Last 30 Days)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
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

            <!-- Filters & Search -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('orders.index') }}" method="GET">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <input type="text" name="search" class="form-control" placeholder="Search by Invoice, ID, Customer..." value="{{ request('search') }}">
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
                                            <option value="">All Payment</option>
                                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="from" class="form-control" value="{{ request('from') }}" placeholder="From Date">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" name="to" class="form-control" value="{{ request('to') }}" placeholder="To Date">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-funnel"></i> Filter
                                        </button>
                                    </div>
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
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">
                                Orders <span class="badge bg-dark-subtle text-dark ms-1">{{ $orders->total() }}</span>
                            </h5>
                            <div>
                                <button class="btn btn-subtle-danger d-none" id="remove-actions">Delete Selected</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0">
                                    <thead class="table-active">
                                        <tr>
                                            <th><input class="form-check-input" type="checkbox" id="checkAll"></th>
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
                                    <tbody class="list form-check-all">
                                        @forelse($orders as $order)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input bulk-checkbox" type="checkbox" value="{{ $order->id }}">
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('orders.show', $order->id) }}" class="fw-bold">
                                                    {{ $order->invoice_number ?? substr($order->id, 0, 8) }}
                                                </a>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3">
                                                        <div class="avatar-title bg-secondary-subtle rounded-circle">
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
                                            <td class="fw-bold">${{ number_format($order->total_amount, 2) }}</td>
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
                                                        <li><a class="dropdown-item" href="{{ route('orders.show', $order->id) }}">View Details</a></li>
                                                        <li><a class="dropdown-item" href="{{ route('orders.invoice', $order->id) }}" target="_blank">PDF Invoice</a></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="emailInvoice('{{ $order->id }}')">Email Invoice</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">No orders found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mt-3 align-items-center">
                                <div class="col-sm">
                                    <div class="text-muted text-center text-sm-start">
                                        Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} Results
                                    </div>
                                </div>
                                <div class="col-sm-auto mt-3 mt-sm-0">
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

{{-- Charts Script --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sales Chart
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: @json($analytics['sales_chart']['labels'] ?? []),
            datasets: [{
                label: 'Sales ($)',
                data: @json($analytics['sales_chart']['data'] ?? []),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } } }
    });

    // Status Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
            datasets: [{
                data: [
                    {{ $stats['pending'] }},
                    {{ $stats['processing'] }},
                    {{ $stats['shipped'] }},
                    {{ $stats['delivered'] }},
                    {{ $stats['cancelled'] }}
                ],
                backgroundColor: ['#ffc107', '#0dcaf0', '#0d6efd', '#198754', '#dc3545']
            }]
        },
        options: { responsive: true }
    });

    // Status Update
    document.querySelectorAll('.status-select').forEach(el => {
        el.addEventListener('change', function() {
            axios.post(`/orders/${this.dataset.id}/status`, { status: this.value })
                .then(() => Swal.fire('Success', 'Status updated!', 'success'))
                .catch(() => this.value = this.dataset.previousValue || 'pending');
        });
    });

    window.emailInvoice = function(id) {
        axios.post(`/orders/${id}/email-invoice`)
            .then(() => Swal.fire('Sent!', 'Invoice emailed', 'success'))
            .catch(() => Swal.fire('Error', 'Failed to send', 'error'));
    };
});
</script>
@endsection