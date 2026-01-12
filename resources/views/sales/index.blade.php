@extends('layouts.master')

@section('title', 'Sales Analytics')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Sales Analytics</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DATE RANGE SUMMARY -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Date Range: {{ date('M d, Y', strtotime($dateFrom)) }} - {{ date('M d, Y', strtotime($dateTo)) }}</h6>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <small class="text-muted">Total Revenue:</small>
                                        <div class="fw-bold">₦{{ number_format($dateRangeSummary['total_revenue'], 2) }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Total Sales:</small>
                                        <div class="fw-bold">{{ number_format($dateRangeSummary['total_sales']) }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Average Sale:</small>
                                        <div class="fw-bold">₦{{ number_format($dateRangeSummary['average_sale'], 2) }}</div>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted">Total Commission:</small>
                                        <div class="fw-bold">₦{{ number_format($dateRangeSummary['total_commission'], 2) }}</div>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="generatePDF()">
                                <i class="bi bi-file-pdf"></i> Generate PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KEY METRICS -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Revenue</p>
                                    <h4 class="fs-22 fw-semibold mb-0">₦{{ number_format($totalRevenue, 2) }}</h4>
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
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Sales</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $totalSalesCount }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info rounded-circle fs-3">
                                        <i class="bi bi-cart"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Commission</p>
                                    <h4 class="fs-22 fw-semibold mb-0">₦{{ number_format($totalCommission, 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success rounded-circle fs-3">
                                        <i class="bi bi-cash-stack"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Pending Commission</p>
                                    <h4 class="fs-22 fw-semibold mb-0">₦{{ number_format($commissions['pending'], 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Sales Person</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">All Sales Persons</option>
                                        @foreach($salesPersons as $person)
                                            <option value="{{ $person->id }}" {{ request('user_id') == $person->id ? 'selected' : '' }}>
                                                {{ $person->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select">
                                        <option value="">All Methods</option>
                                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>Card</option>
                                        <option value="transfer" {{ request('payment_method') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                                        <option value="pos" {{ request('payment_method') == 'pos' ? 'selected' : '' }}>POS</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-filter"></i> Apply Filters
                                    </button>
                                    <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="row mt-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Monthly Revenue Trend</h5>
                            <span class="badge bg-info">Last 12 Months</span>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Top Performers ({{ date('M d, Y', strtotime($dateFrom)) }} - {{ date('M d, Y', strtotime($dateTo)) }})</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                @forelse($topPerformers as $index => $p)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary me-2">{{ $index + 1 }}</span>
                                            <span>{{ $p->name }}</span>
                                        </div>
                                        <div class="text-end">
                                            <strong>₦{{ number_format($p->revenue, 0) }}</strong><br>
                                            <small class="text-muted">{{ $p->order_count }} sales</small>
                                        </div>
                                    </li>
                                @empty
                                    <li class="list-group-item text-center text-muted">
                                        No data available for selected period
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Methods Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="paymentChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SALES TABLE -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sales History ({{ $sales->total() }} records)</h5>
                    <div>
                        @if(request('user_id'))
                            <a href="{{ route('sales.user.pdf', ['userId' => request('user_id'), 'date_from' => $dateFrom, 'date_to' => $dateTo, 'payment_method' => request('payment_method')]) }}"
                               class="btn btn-danger" target="_blank">
                                <i class="bi bi-file-pdf"></i> Export User PDF
                            </a>
                        @endif
                        <button class="btn btn-success ms-2" onclick="openPDFModal()">
                            <i class="bi bi-eye"></i> Preview PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Sales Person</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Commission</th>
                                    <th>Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                    <tr>
                                        <td>#{{ $sale->id }}</td>
                                        <td>{{ $sale->order_date->format('M d, Y') }}</td>
                                        <td>
                                            @if($sale->user)
                                                <a href="{{ route('sales.user', ['userId' => $sale->user_id, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                                                   class="text-primary">
                                                    {{ $sale->user->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $sale->customer->name ?? 'Guest' }}</td>
                                        <td>{{ $sale->items->count() }}</td>
                                        <td>₦{{ number_format($sale->total_amount, 2) }}</td>
                                        <td>₦{{ number_format($sale->commission_amount ?? 0, 2) }}</td>
                                        <td><span class="badge bg-{{ $sale->payment_method == 'cash' ? 'success' : ($sale->payment_method == 'card' ? 'info' : ($sale->payment_method == 'transfer' ? 'warning' : 'danger')) }}">{{ ucfirst($sale->payment_method) }}</span></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary view-order-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#orderModal"
                                                    data-id="{{ $sale->id }}">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No sales recorded for selected period</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {!! $sales->links() !!}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetails">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading order details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- PDF Preview Modal -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfPreviewModalLabel">PDF Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading PDF...</span>
                    </div>
                    <p class="mt-2">Generating PDF preview...</p>
                </div>
                <iframe id="pdfFrame" style="width: 100%; height: 600px; border: none; display: none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="downloadPDF()">
                    <i class="bi bi-download"></i> Download PDF
                </button>
                <button type="button" class="btn btn-success" onclick="printPDF()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize charts
    initCharts();

    // Order Modal AJAX
    document.querySelectorAll('.view-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            loadOrderDetails(orderId);
        });
    });

    // Load order details when modal is shown
    const orderModal = document.getElementById('orderModal');
    orderModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const orderId = button.getAttribute('data-id');
        loadOrderDetails(orderId);
    });

    // PDF Preview Modal
    const pdfModal = document.getElementById('pdfPreviewModal');
    pdfModal.addEventListener('show.bs.modal', function() {
        loadPDFPreview();
    });
});

function initCharts() {
    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart');
    const months = @json($monthlySales->keys());
    const values = @json($monthlySales->values());

    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue (₦)',
                    data: values,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₦' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₦' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // Payment Chart
    const paymentCtx = document.getElementById('paymentChart');
    const paymentMethods = @json($paymentBreakdown->pluck('payment_method'));
    const paymentTotals = @json($paymentBreakdown->pluck('total'));

    if (paymentCtx) {
        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: paymentMethods,
                datasets: [{
                    data: paymentTotals,
                    backgroundColor: [
                        '#198754', // Cash - Green
                        '#0d6efd', // Card - Blue
                        '#ffc107', // Transfer - Yellow
                        '#dc3545', // POS - Red
                        '#6f42c1'  // Other - Purple
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${context.label}: ₦${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}


function loadOrderDetails(orderId) {
    const orderDetailsDiv = document.getElementById('orderDetails');

    // Show loading
    orderDetailsDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading order details...</p>
        </div>
    `;

    // Update modal title
    document.getElementById('orderModalLabel').textContent = `Order #${orderId} Details`;

    // Fetch order details via AJAX
    fetch(`/sales/${orderId}/details`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const order = data.order;

                // Format date
                const orderDate = new Date(order.order_date);
                const formattedDate = orderDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Get user name safely
                const userName = order.user ? order.user.name : 'System';
                const userEmail = order.user ? order.user.email : 'N/A';

                // Build HTML content
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Order Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Order ID:</th>
                                            <td>#${order.id}</td>
                                        </tr>
                                        <tr>
                                            <th>Date & Time:</th>
                                            <td>${formattedDate}</td>
                                        </tr>
                                        <tr>
                                            <th>Status:</th>
                                            <td><span class="badge bg-${order.status === 'completed' ? 'success' : (order.status === 'pending' ? 'warning' : 'info')}">${order.status.toUpperCase()}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Payment Method:</th>
                                            <td><span class="badge bg-${order.payment_method === 'cash' ? 'success' : (order.payment_method === 'card' ? 'info' : (order.payment_method === 'transfer' ? 'warning' : 'danger'))}">${order.payment_method.toUpperCase()}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Total Amount:</th>
                                            <td class="fw-bold">₦${parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                        </tr>
                                        <tr>
                                            <th>Commission:</th>
                                            <td class="fw-bold text-success">₦${parseFloat(order.commission_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="mb-0">Customer & Sales Person</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th>Customer:</th>
                                            <td>${order.customer ? order.customer.name : 'Guest'}</td>
                                        </tr>
                                        <tr>
                                            <th>Customer Phone:</th>
                                            <td>${order.customer ? (order.customer.phone || 'N/A') : 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <th>Sales Person:</th>
                                            <td>${userName}</td>
                                        </tr>
                                        <tr>
                                            <th>Sales Person Email:</th>
                                            <td>${userEmail}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Add items table if items exist
                if (order.items && order.items.length > 0) {
                    html += `
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Order Items (${order.items.length})</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    let itemsTotal = 0;

                    order.items.forEach((item, index) => {
                        // Safely get product name - check both 'name' and 'title' properties
                        const productName = item.product ? (item.product.name || item.product.title) : `Product #${item.product_id || 'N/A'}`;
                        const quantity = parseInt(item.quantity) || 0;
                        const unitPrice = parseFloat(item.price) || 0;
                        const itemTotal = parseFloat(item.total) || (quantity * unitPrice);
                        itemsTotal += itemTotal;

                        html += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${productName}</td>
                                <td>${quantity}</td>
                                <td>₦${unitPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td>₦${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        `;
                    });

                    html += `
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">Items Total:</td>
                                                <td class="fw-bold">₦${itemsTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-end">Tax/VAT:</td>
                                                <td>₦0.00</td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">Grand Total:</td>
                                                <td class="fw-bold text-success">₦${parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Add notes if available
                if (order.notes) {
                    html += `
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0">Notes</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${order.notes}</p>
                            </div>
                        </div>
                    `;
                }

                orderDetailsDiv.innerHTML = html;
            } else {
                orderDetailsDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> ${data.message || 'Failed to load order details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            orderDetailsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> An error occurred while loading order details. Please try again.
                    <br><small>Error: ${error.message}</small>
                </div>
            `;
        });
}

function generatePDF() {
    // Build URL with current filters
    let url = '/sales/export/pdf?';
    const params = new URLSearchParams(window.location.search);

    // Add date range if not in params
    if (!params.has('date_from')) {
        params.append('date_from', '{{ $dateFrom }}');
    }
    if (!params.has('date_to')) {
        params.append('date_to', '{{ $dateTo }}');
    }

    url += params.toString();

    // Open in new tab
    window.open(url, '_blank');
}

function openPDFModal() {
    const modal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
    modal.show();
}

function loadPDFPreview() {
    const pdfFrame = document.getElementById('pdfFrame');
    const spinner = pdfFrame.previousElementSibling;

    // Build URL with current filters
    let url = '/sales/export/pdf?';
    const params = new URLSearchParams(window.location.search);

    // Add date range if not in params
    if (!params.has('date_from')) {
        params.append('date_from', '{{ $dateFrom }}');
    }
    if (!params.has('date_to')) {
        params.append('date_to', '{{ $dateTo }}');
    }

    url += params.toString();

    // Set iframe source
    pdfFrame.src = url;

    // Show iframe when loaded
    pdfFrame.onload = function() {
        spinner.style.display = 'none';
        pdfFrame.style.display = 'block';
    };
}

function downloadPDF() {
    // Build URL with current filters
    let url = '/sales/export/pdf?download=1&';
    const params = new URLSearchParams(window.location.search);

    // Add date range if not in params
    if (!params.has('date_from')) {
        params.append('date_from', '{{ $dateFrom }}');
    }
    if (!params.has('date_to')) {
        params.append('date_to', '{{ $dateTo }}');
    }

    url += params.toString();

    // Trigger download
    window.location.href = url;
}

function printPDF() {
    const pdfFrame = document.getElementById('pdfFrame');
    pdfFrame.contentWindow.print();
}
</script>
@endsection
