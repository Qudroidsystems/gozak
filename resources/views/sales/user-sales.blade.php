@extends('layouts.master')

@section('title', 'User Sales Report')

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
                                <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                                <li class="breadcrumb-item active">User Sales</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- USER PROFILE & SUMMARY -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar-xl mx-auto mb-3">
                                <div class="avatar-title bg-primary rounded-circle display-4">
                                    @php
                                        $userName = $user->name ?? $user->first_name . ' ' . $user->last_name;
                                    @endphp
                                    {{ strtoupper(substr($userName, 0, 1)) }}
                                </div>
                            </div>
                            <h5 class="mb-1">{{ $userName }}</h5>
                            <p class="text-muted mb-2">{{ $user->email }}</p>
                            <p class="text-muted">{{ $user->phone ?? 'No phone' }}</p>
                            <div class="mt-3">
                                <span class="badge bg-info">Sales Person</span>
                                @if($user->created_at)
                                    <p class="text-muted mt-2 mb-0">
                                        <small>Joined: {{ $user->created_at->format('M d, Y') }}</small>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Performance Summary ({{ date('M d, Y', strtotime($dateFrom)) }} - {{ date('M d, Y', strtotime($dateTo)) }})</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h3 class="text-primary">₦{{ number_format($userSummary['total_revenue'], 2) }}</h3>
                                        <p class="text-muted mb-0">Total Revenue</p>
                                        <small class="text-muted">{{ $userSummary['total_sales'] }} sales</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h3 class="text-success">{{ number_format($userSummary['total_sales']) }}</h3>
                                        <p class="text-muted mb-0">Total Sales</p>
                                        <small class="text-muted">
                                            @if($userSummary['total_sales'] > 0)
                                                {{ number_format($userSummary['total_sales'] / $daysInPeriod, 1) }}/day
                                            @else
                                                0/day
                                            @endif
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h3 class="text-warning">₦{{ number_format($userSummary['total_commission'], 2) }}</h3>
                                        <p class="text-muted mb-0">Total Commission</p>
                                        <small class="text-muted">
                                            @if($userSummary['total_revenue'] > 0)
                                                {{ number_format(($userSummary['total_commission'] / $userSummary['total_revenue']) * 100, 1) }}% rate
                                            @else
                                                0% rate
                                            @endif
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h3 class="text-info">₦{{ number_format($userSummary['average_sale'], 2) }}</h3>
                                        <p class="text-muted mb-0">Average Sale</p>
                                        <small class="text-muted">
                                            @if($userPerformance->avg_order_value ?? 0 > 0)
                                                @php
                                                    $trend = $userSummary['average_sale'] > $userPerformance->avg_order_value ? 'up' : 'down';
                                                @endphp
                                                <i class="bi bi-arrow-{{ $trend }}-circle text-{{ $trend == 'up' ? 'success' : 'danger' }}"></i>
                                                {{ number_format(abs($userSummary['average_sale'] - $userPerformance->avg_order_value), 2) }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DATE RANGE FILTER -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Sales Period: {{ date('M d, Y', strtotime($dateFrom)) }} - {{ date('M d, Y', strtotime($dateTo)) }}</h6>
                                    <small class="text-muted">Showing {{ $sales->total() }} sales records</small>
                                </div>
                                <div>
                                    <button class="btn btn-primary" onclick="generateUserPDF()">
                                        <i class="bi bi-file-pdf"></i> Generate PDF
                                    </button>
                                    <button class="btn btn-success ms-2" onclick="openUserPDFModal()">
                                        <i class="bi bi-eye"></i> Preview PDF
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <div class="col-md-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}" required>
                                </div>
                                <div class="col-md-3">
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
                                    <a href="{{ route('sales.user', ['userId' => $user->id]) }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PERFORMANCE METRICS -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Overall Performance</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Total Orders (All Time):</strong></td>
                                    <td class="text-end">{{ number_format($userPerformance->total_orders ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Revenue (All Time):</strong></td>
                                    <td class="text-end">₦{{ number_format($userPerformance->total_revenue ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Average Order Value:</strong></td>
                                    <td class="text-end">₦{{ number_format($userPerformance->avg_order_value ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Largest Order:</strong></td>
                                    <td class="text-end">₦{{ number_format($userPerformance->largest_order ?? 0, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Smallest Order:</strong></td>
                                    <td class="text-end">₦{{ number_format($userPerformance->smallest_order ?? 0, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Monthly Performance</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th class="text-end">Orders</th>
                                            <th class="text-end">Revenue</th>
                                            <th class="text-end">Avg. Order</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($monthlyPerformance as $month)
                                            <tr>
                                                <td>{{ date('M Y', strtotime($month->month . '-01')) }}</td>
                                                <td class="text-end">{{ number_format($month->order_count) }}</td>
                                                <td class="text-end">₦{{ number_format($month->total_revenue, 2) }}</td>
                                                <td class="text-end">₦{{ number_format($month->order_count > 0 ? $month->total_revenue / $month->order_count : 0, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- USER SALES TABLE -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $userName }}'s Sales History</h5>
                    <div>
                        <span class="badge bg-primary">Total: ₦{{ number_format($userSummary['total_revenue'], 2) }}</span>
                        <span class="badge bg-success ms-2">Sales: {{ number_format($userSummary['total_sales']) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Commission</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                    <tr>
                                        <td>#{{ $sale->id }}</td>
                                        <td>{{ $sale->order_date->format('M d, Y') }}</td>
                                        <td>{{ $sale->customer->name ?? 'Guest' }}</td>
                                        <td>{{ $sale->items->count() }}</td>
                                        <td>₦{{ number_format($sale->total_amount, 2) }}</td>
                                        <td>₦{{ number_format($sale->commission_amount ?? 0, 2) }}</td>
                                        <td><span class="badge bg-{{ $sale->payment_method == 'cash' ? 'success' : ($sale->payment_method == 'card' ? 'info' : ($sale->payment_method == 'transfer' ? 'warning' : 'danger')) }}">{{ ucfirst($sale->payment_method) }}</span></td>
                                        <td><span class="badge bg-{{ $sale->status == 'completed' ? 'success' : ($sale->status == 'pending' ? 'warning' : 'info') }}">{{ ucfirst($sale->status) }}</span></td>
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
                <h5 class="modal-title" id="pdfPreviewModalLabel">PDF Preview - {{ $userName }}</h5>
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
                <button type="button" class="btn btn-primary" onclick="downloadUserPDF()">
                    <i class="bi bi-download"></i> Download PDF
                </button>
                <button type="button" class="btn btn-success" onclick="printPDF()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
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





function generateUserPDF() {
    // Build URL with current filters
    let url = `/sales/user/{{ $user->id }}/export/pdf?`;
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

function openUserPDFModal() {
    const modal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
    modal.show();
}

function loadPDFPreview() {
    const pdfFrame = document.getElementById('pdfFrame');
    const spinner = pdfFrame.previousElementSibling;

    // Build URL with current filters
    let url = `/sales/user/{{ $user->id }}/export/pdf?`;
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

function downloadUserPDF() {
    // Build URL with current filters
    let url = `/sales/user/{{ $user->id }}/export/pdf?download=1&`;
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
