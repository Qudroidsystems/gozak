@extends('layouts.master')

@section('title', 'Low Stock Alerts')

@push('styles')
<style>
    .stock-level-critical {
        background-color: #fdecea !important;
        border-left: 4px solid #dc3545 !important;
    }
    .stock-level-low {
        background-color: #fff8e6 !important;
        border-left: 4px solid #ffc107 !important;
    }
    .stock-level-warning {
        background-color: #e8f4fd !important;
        border-left: 4px solid #0dcaf0 !important;
    }
    .progress-bar-danger {
        background-color: #dc3545;
    }
    .progress-bar-warning {
        background-color: #ffc107;
    }
    .progress-bar-info {
        background-color: #0dcaf0;
    }
    .stock-progress {
        height: 6px;
        border-radius: 3px;
    }
    .badge-outline {
        background-color: transparent;
        border: 1px solid;
    }
    .alert-sync {
        background-color: #e8f5e9;
        border-color: #c3e6cb;
    }
</style>
@endpush

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Low Stock Alerts' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                                <li class="breadcrumb-item active">Low Stock Alerts</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUCCESS/ERROR MESSAGES -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <!-- STOCK SYNC ALERT -->
            <div class="alert alert-sync alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">
                            <i class="bi bi-info-circle me-2"></i> Stock Calculation Method
                        </h6>
                        <p class="mb-0">
                            Low stock is calculated from actual inventory transactions, not the product's stock field.
                            <span class="fw-semibold">Threshold: ≤ {{ $threshold }} units</span>
                        </p>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="{{ route('inventory.low-stock-alerts', array_merge(request()->query(), ['recalculate' => 1])) }}"
                           class="btn btn-sm btn-outline-warning me-2"
                           onclick="return confirm('This will recalculate all product stocks from inventory transactions. Continue?')">
                            <i class="bi bi-arrow-repeat me-1"></i> Recalculate All
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            </div>

            <!-- DEBUG PANEL (Only in debug mode) -->
            @if(config('app.debug') && !empty($debugInfo))
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary bg-opacity-10 border-primary">
                    <h6 class="mb-0 text-primary">
                        <i class="bi bi-bug me-1"></i> Debug Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded">
                                <h6 class="mb-2">Statistics</h6>
                                <p class="mb-1">Total Products: <strong>{{ $totalProducts }}</strong></p>
                                <p class="mb-1">Products with Stock: <strong>{{ $productsWithStock }}</strong></p>
                                <p class="mb-1">Low Stock Products: <strong>{{ $totalLowStock }}</strong></p>
                                <p class="mb-0">Current Threshold: <strong>≤ {{ $threshold }} units</strong></p>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="mb-2">Sample Product Calculations</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product</th>
                                            <th>DB Stock</th>
                                            <th>Calculated</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($debugInfo as $debug)
                                        <tr>
                                            <td>{{ $debug['id'] }}</td>
                                            <td>{{ Str::limit($debug['name'], 20) }}</td>
                                            <td class="{{ $debug['db_stock'] != $debug['calculated_stock'] ? 'text-danger fw-bold' : '' }}">
                                                {{ $debug['db_stock'] }}
                                            </td>
                                            <td>{{ $debug['calculated_stock'] }}</td>
                                            <td>
                                                @if($debug['is_low'])
                                                    <span class="badge bg-danger">Low Stock</span>
                                                @else
                                                    <span class="badge bg-success">OK</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Red text indicates DB stock differs from calculated stock
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- SUMMARY CARDS -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-primary border-top">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Low Stock Items</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $totalLowStock }}</h4>
                                    <p class="mb-0">
                                        <small class="text-muted">
                                            ≤ {{ $threshold }} units
                                            @if($totalLowStock > 0)
                                                <span class="badge bg-danger ms-2">Alert</span>
                                            @endif
                                        </small>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-danger border-top">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Critical Items</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $criticalCount }}</h4>
                                    <p class="mb-0">
                                        <small class="text-muted">≤ 3 units (Urgent)</small>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                        <i class="bi bi-exclamation-octagon"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-warning border-top">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Action Required</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $actionRequiredCount }}</h4>
                                    <p class="mb-0">
                                        <small class="text-muted">≤ 5 units (Priority)</small>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                        <i class="bi bi-lightning-charge"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card border-success border-top">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-1">Total Products</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $totalProducts }}</h4>
                                    <p class="mb-0">
                                        <small class="text-muted">
                                            {{ $productsWithStock }} with stock
                                        </small>
                                    </p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                        <i class="bi bi-box-seam"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" id="filterForm">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select class="form-control" name="category_id">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Brand</label>
                                <select class="form-control" name="brand_id">
                                    <option value="">All Brands</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock Threshold</label>
                                <select class="form-control" name="threshold" id="thresholdSelect">
                                    <option value="3" {{ request('threshold', 10) == 3 ? 'selected' : '' }}>≤ 3 units (Critical)</option>
                                    <option value="5" {{ request('threshold', 10) == 5 ? 'selected' : '' }}>≤ 5 units (High Priority)</option>
                                    <option value="10" {{ request('threshold', 10) == 10 ? 'selected' : '' }}>≤ 10 units (Standard)</option>
                                    <option value="20" {{ request('threshold') == 20 ? 'selected' : '' }}>≤ 20 units (Watch List)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Product name, SKU...">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12 d-flex justify-content-between">
                                <div>
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-funnel me-1"></i> Apply Filters
                                    </button>
                                    <a href="{{ route('inventory.low-stock-alerts') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Reset
                                    </a>
                                </div>
                                <div>
                                    @if($products->total() > 0)
                                        <button type="button" class="btn btn-success me-2" onclick="exportReport()">
                                            <i class="bi bi-download me-1"></i> Export
                                        </button>
                                    @endif
                                    <a href="{{ route('inventory.stock-levels') }}" class="btn btn-info">
                                        <i class="bi bi-box-seam me-1"></i> All Stock
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- LOW STOCK PRODUCTS -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Low Stock Products
                        @if($products->total() > 0)
                            <span class="badge bg-danger ms-2">{{ $products->total() }}</span>
                            <small class="text-muted ms-2">(≤ {{ $threshold }} units)</small>
                        @endif
                    </h5>
                    @if($products->total() > 0)
                    <div class="d-flex align-items-center">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="showCritical" checked>
                            <label class="form-check-label" for="showCritical">
                                Critical
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="showLow" checked>
                            <label class="form-check-label" for="showLow">
                                Low
                            </label>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="card-body">
                    @if($products->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-centered align-middle table-nowrap mb-0" id="lowStockTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Brand</th>
                                        <th>Current Stock</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Reorder Level</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                        @php
                                            // Use calculated stock from controller
                                            $stock = $product->current_calculated_stock;
                                            $reorderLevel = $product->reorder_level ?? 10;
                                            $stockPercentage = min(100, ($stock / $reorderLevel) * 100);

                                            if($stock <= 3) {
                                                $rowClass = 'stock-level-critical';
                                                $statusClass = 'danger';
                                                $statusText = 'Critical';
                                                $progressClass = 'progress-bar-danger';
                                                $stockClass = 'text-danger fw-bold';
                                                $priority = 'critical';
                                            } elseif($stock <= 5) {
                                                $rowClass = 'stock-level-low';
                                                $statusClass = 'warning';
                                                $statusText = 'Low';
                                                $progressClass = 'progress-bar-warning';
                                                $stockClass = 'text-warning fw-bold';
                                                $priority = 'low';
                                            } else {
                                                $rowClass = 'stock-level-warning';
                                                $statusClass = 'info';
                                                $statusText = 'Warning';
                                                $progressClass = 'progress-bar-info';
                                                $stockClass = 'text-info fw-semibold';
                                                $priority = 'warning';
                                            }
                                        @endphp
                                        <tr class="{{ $rowClass }}" data-priority="{{ $priority }}">
                                            <td>{{ $loop->iteration + (($products->currentPage() - 1) * $products->perPage()) }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($product->thumbnail)
                                                        <img src="{{ asset('storage/' . $product->thumbnail) }}"
                                                             class="rounded me-2" width="40" height="40"
                                                             alt="{{ $product->title }}">
                                                    @else
                                                        <div class="rounded bg-light d-flex align-items-center justify-content-center me-2"
                                                             style="width: 40px; height: 40px;">
                                                            <i class="bi bi-box text-muted"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <div class="fw-semibold">{{ $product->title }}</div>
                                                        <small class="text-muted">{{ $product->sku }}</small>
                                                        <div class="mt-1">
                                                            <div class="progress stock-progress" style="width: 100px;">
                                                                <div class="progress-bar {{ $progressClass }}"
                                                                     role="progressbar"
                                                                     style="width: {{ $stockPercentage }}%"
                                                                     aria-valuenow="{{ $stock }}"
                                                                     aria-valuemin="0"
                                                                     aria-valuemax="{{ $reorderLevel }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @if(config('app.debug') && $product->stock != $stock)
                                                        <small class="text-danger">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            DB: {{ $product->stock }} | Calc: {{ $stock }}
                                                        </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($product->category)
                                                    <span class="badge bg-light text-dark">{{ $product->category->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($product->brand)
                                                    <span class="badge bg-light text-dark">{{ $product->brand->name }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="{{ $stockClass }} me-2">
                                                        {{ $stock }}
                                                    </span>
                                                    @if($product->stock != $stock)
                                                        <span class="badge bg-warning" data-bs-toggle="tooltip" title="DB stock differs from calculated stock">
                                                            <i class="bi bi-exclamation-triangle"></i>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>${{ number_format($product->price, 2) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ $statusText }}
                                                </span>
                                            </td>
                                            <td>{{ $reorderLevel }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="{{ route('products.show', $product->id) }}"
                                                       class="btn btn-outline-info"
                                                       title="View Product">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('inventory.history', $product->id) }}"
                                                       class="btn btn-outline-primary"
                                                       title="Stock History">
                                                        <i class="bi bi-clock-history"></i>
                                                    </a>
                                                    @can('Adjust stock')
                                                    <button type="button"
                                                            class="btn btn-success adjust-stock-btn"
                                                            data-product-id="{{ $product->id }}"
                                                            data-product-name="{{ $product->title }}"
                                                            data-current-stock="{{ $stock }}"
                                                            title="Adjust Stock">
                                                        <i class="bi bi-plus-slash-minus"></i>
                                                    </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="row mt-3 align-items-center">
                            <div class="col-sm">
                                <div class="text-muted text-center text-sm-start">
                                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} Low Stock Products
                                    @if($threshold != 10)
                                        <br><small>Threshold: ≤ {{ $threshold }} units</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-auto mt-3 mt-sm-0">
                                {!! $products->appends(request()->query())->links('pagination::bootstrap-5') !!}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="bi bi-check2-circle display-1 text-success"></i>
                            </div>
                            <h4 class="text-success mb-3">Excellent! No Low Stock Items</h4>
                            <p class="text-muted mb-4">
                                All products are sufficiently stocked above the threshold of {{ $threshold }} units.
                            </p>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                <button type="button" class="btn btn-primary" onclick="checkLowerThreshold()">
                                    <i class="bi bi-search me-1"></i> Check with Lower Threshold
                                </button>
                                <a href="{{ route('inventory.stock-levels') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-box-seam me-1"></i> View All Stock Levels
                                </a>
                                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-list-ul me-1"></i> View All Products
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<!-- QUICK ADJUST STOCK MODAL -->
<div class="modal fade" id="quickAdjustModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="quickAdjustForm" method="POST" action="{{ route('inventory.adjust') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Quick Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <input type="text" class="form-control" id="adjustProductName" readonly>
                        <input type="hidden" name="product_id" id="adjustProductId">
                    </div>
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle me-2"></i>
                            <span id="currentStockDisplay">Current Stock: Loading...</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="adjustment_type" class="form-control" required id="adjustmentType">
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock to</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="quantityLabel">Quantity</label>
                        <input type="number" name="quantity" class="form-control" required min="1" value="1" id="adjustmentQuantity">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <select name="location_id" class="form-control" required id="adjustmentLocation">
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->is_default ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" required
                               placeholder="e.g., Restock, Found extra stock, etc.">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="quickAdjustBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="quickAdjustSpinner"></span>
                        Apply Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@if($products->count() > 0)
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable) {
        $('#lowStockTable').DataTable({
            pageLength: 25,
            responsive: true,
            order: [[4, 'asc']], // Sort by stock ascending
            language: {
                search: "Search low stock items:",
                info: "Showing _START_ to _END_ of _TOTAL_ low stock items",
                infoEmpty: "No low stock items found",
                lengthMenu: "Show _MENU_ items"
            },
            dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
        });
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Filter by priority
    $('#showCritical, #showLow').on('change', function() {
        filterTable();
    });

    function filterTable() {
        var showCritical = $('#showCritical').is(':checked');
        var showLow = $('#showLow').is(':checked');

        $('#lowStockTable tbody tr').each(function() {
            var priority = $(this).data('priority');
            var show = false;

            if (priority === 'critical' && showCritical) show = true;
            if (priority === 'low' && showLow) show = true;
            if (priority === 'warning') show = true; // Always show warnings

            if (show) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set CSRF token for all axios requests
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // Auto-submit threshold filter when changed
    document.getElementById('thresholdSelect')?.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });

    // Check lower threshold function
    window.checkLowerThreshold = function() {
        const currentThreshold = {{ $threshold }};
        let newThreshold;

        if (currentThreshold > 5) {
            newThreshold = 5;
        } else if (currentThreshold > 3) {
            newThreshold = 3;
        } else {
            newThreshold = 10;
        }

        window.location.href = "{{ route('inventory.low-stock-alerts') }}?threshold=" + newThreshold;
    };

    // Export report function
    window.exportReport = function() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'low_stock');
        window.open(`{{ route('inventory.export.stock-levels') }}?${params.toString()}`, '_blank');
    };

    // Quick adjust stock button
    document.addEventListener('click', function(e) {
        const adjustBtn = e.target.closest('.adjust-stock-btn');
        if (adjustBtn) {
            e.preventDefault();

            const productId = adjustBtn.dataset.productId;
            const productName = adjustBtn.dataset.productName;
            const currentStock = adjustBtn.dataset.currentStock;

            document.getElementById('adjustProductId').value = productId;
            document.getElementById('adjustProductName').value = productName;
            document.getElementById('currentStockDisplay').textContent = `Current Stock: ${currentStock} units`;

            const modal = new bootstrap.Modal(document.getElementById('quickAdjustModal'));
            modal.show();

            // Load location-specific stock
            const locationSelect = document.getElementById('adjustmentLocation');
            if (locationSelect.value) {
                loadLocationStock(productId, locationSelect.value);
            }
        }
    });

    // Handle location change
    document.getElementById('adjustmentLocation')?.addEventListener('change', function() {
        const productId = document.getElementById('adjustProductId')?.value;
        if (productId && this.value) {
            loadLocationStock(productId, this.value);
        }
    });

    // Handle adjustment type change
    document.getElementById('adjustmentType')?.addEventListener('change', function() {
        const label = document.getElementById('quantityLabel');
        switch(this.value) {
            case 'add':
                label.textContent = 'Quantity to Add';
                break;
            case 'remove':
                label.textContent = 'Quantity to Remove';
                break;
            case 'set':
                label.textContent = 'Set Stock To';
                break;
        }
    });

    // Load location-specific stock
    function loadLocationStock(productId, locationId) {
        axios.get(`/inventory/stock-level/${productId}/${locationId}`)
            .then(response => {
                if (response.data.success) {
                    const stock = response.data.stock;
                    const locationName = response.data.location.name;
                    document.getElementById('currentStockDisplay').innerHTML =
                        `<strong>Current Stock at ${locationName}: ${stock} units</strong>`;
                }
            })
            .catch(error => {
                console.error('Error loading stock:', error);
                document.getElementById('currentStockDisplay').innerHTML =
                    '<span class="text-danger">Unable to load current stock information</span>';
            });
    }

    // Quick adjust form submission
    document.getElementById('quickAdjustForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const btn = document.getElementById('quickAdjustBtn');
        const spinner = document.getElementById('quickAdjustSpinner');
        if (btn) btn.disabled = true;
        if (spinner) spinner.classList.remove('d-none');

        const formData = new FormData(this);

        axios.post('{{ route("inventory.adjust") }}', formData)
            .then(response => {
                if (response.data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('quickAdjustModal'));
                        if (modal) modal.hide();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.data.message
                    });
                }
            })
            .catch(error => {
                let errorMessage = 'Failed to adjust stock';
                if (error.response?.data?.errors) {
                    errorMessage = Object.values(error.response.data.errors).flat().join('<br>');
                } else if (error.response?.data?.message) {
                    errorMessage = error.response.data.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage
                });
            })
            .finally(() => {
                if (btn) btn.disabled = false;
                if (spinner) spinner.classList.add('d-none');
            });
    });

    // Show success message if no low stock items
    @if($products->count() === 0 && !session('success') && !session('error'))
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });

    Toast.fire({
        icon: 'success',
        title: 'All products are sufficiently stocked!'
    });
    @endif
});
</script>
@endsection
