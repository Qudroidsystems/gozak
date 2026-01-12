@extends('layouts.master')

@section('title', 'Stock Levels')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Stock Levels' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                                <li class="breadcrumb-item active">Stock Levels</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY CARDS -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Products</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bi bi-box-seam fs-2 text-primary"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ number_format($summary['total_products']) }}</h4>
                                    <span class="badge bg-secondary-subtle text-secondary">All Items</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">In Stock</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bi bi-check-circle fs-2 text-success"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ number_format($summary['in_stock']) }}</h4>
                                    <span class="badge bg-success-subtle text-success">> 10 units</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Low Stock</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bi bi-exclamation-triangle fs-2 text-warning"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ number_format($summary['low_stock']) }}</h4>
                                    <span class="badge bg-warning-subtle text-warning">1–10 units</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Out of Stock</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bi bi-x-circle fs-2 text-danger"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ number_format($summary['out_of_stock']) }}</h4>
                                    <span class="badge bg-danger-subtle text-danger">0 units</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VALUE SUMMARY CARDS -->
            <div class="row mt-3">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Cost Value</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bi bi-cash fs-2 text-info"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-1">₦<span id="totalCostValue">0.00</span></h4>
                                    <span class="badge bg-info-subtle text-info">Cost Value</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Selling Value</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bi bi-cash-stack fs-2 text-success"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-1">₦<span id="totalSellingValue">0.00</span></h4>
                                    <span class="badge bg-success-subtle text-success">Selling Value</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Potential Profit</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bi bi-graph-up fs-2 text-primary"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-1">₦<span id="totalPotentialProfit">0.00</span></h4>
                                    <span class="badge bg-primary-subtle text-primary">Gross Profit</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Avg. Margin %</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <i class="bi bi-percent fs-2 text-warning"></i>
                                </div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4">
                                <div>
                                    <h4 class="fs-22 fw-semibold ff-secondary mb-1"><span id="avgMarginPercent">0.00</span>%</h4>
                                    <span class="badge bg-warning-subtle text-warning">Average Margin</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="row mt-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Stock Status Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="stockStatusChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Total Stock by Location</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="stockByLocationChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROFIT MARGIN CHART -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Profit Margin Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="marginDistributionChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="card mt-4">
                <div class="card-body">
                    <form method="GET" id="stockLevelsForm">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Stock Status</label>
                                <select name="stock_status" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="in_stock" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>In Stock (>10)</option>
                                    <option value="low_stock" {{ request('stock_status') == 'low_stock' ? 'selected' : '' }}>Low Stock (1-10)</option>
                                    <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-control" onchange="this.form.submit()">
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
                                <select name="brand_id" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Brands</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search products...">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i>
                                    </button>
                                    <a href="{{ route('inventory.stock-levels') }}" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <label class="form-label">Sort By</label>
                                <div class="input-group">
                                    <select name="sort_by" class="form-control" onchange="this.form.submit()">
                                        <option value="total_stock" {{ request('sort_by') == 'total_stock' ? 'selected' : '' }}>Stock Quantity</option>
                                        <option value="title" {{ request('sort_by') == 'title' ? 'selected' : '' }}>Product Name</option>
                                        <option value="sku" {{ request('sort_by') == 'sku' ? 'selected' : '' }}>SKU</option>
                                        <option value="price" {{ request('sort_by') == 'price' ? 'selected' : '' }}>Base Price</option>
                                        <option value="sale_price" {{ request('sort_by') == 'sale_price' ? 'selected' : '' }}>Selling Price</option>
                                        <option value="cost_price" {{ request('sort_by') == 'cost_price' ? 'selected' : '' }}>Cost Price</option>
                                        <option value="margin_percent" {{ request('sort_by') == 'margin_percent' ? 'selected' : '' }}>Margin %</option>
                                    </select>
                                    <select name="sort_order" class="form-control" onchange="this.form.submit()">
                                        <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                        <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Descending</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- STOCK LEVELS TABLE -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Stock Levels by Location</h5>
                    <div>
                        @can('Manage inventory')
                            <button type="button" class="btn btn-info me-2" onclick="openBulkAdjustModal()">
                                <i class="bi bi-plus-slash-minus me-1"></i> Bulk Adjust
                            </button>
                        @endcan
                        <a href="{{ route('inventory.export.stock-levels') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success me-2">
                            <i class="bi bi-download me-1"></i> Export CSV
                        </a>
                        <a href="{{ route('inventory.export.stock-levels.pdf') }}?{{ http_build_query(request()->query()) }}" class="btn btn-danger" target="_blank">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-centered align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" id="selectAll"></th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Cost Price</th>
                                    <th>Base Price</th>
                                    <th>Selling Price</th>
                                    <th>Discount</th>
                                    <th>Profit Margin</th>
                                    <th>Profit %</th>
                                    @foreach($locations as $location)
                                        <th class="text-center">{{ $location->name }}</th>
                                    @endforeach
                                    <th>Total Stock</th>
                                    <th>Stock Value</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $data)
                                    @php
                                        // Handle both array format and object format
                                        if (is_array($data) && isset($data['product'])) {
                                            $product = $data['product'];
                                            $totalStock = $data['total_stock'] ?? 0;
                                        } else {
                                            $product = $data;
                                            // Calculate total stock from locationStockData
                                            $totalStock = 0;
                                            if (isset($locationStockData[$product->id])) {
                                                foreach($locations as $location) {
                                                    $stock = $locationStockData[$product->id][$location->id] ?? 0;
                                                    $totalStock += $stock;
                                                }
                                            }
                                        }

                                        $costPrice = $product->cost_price ?? 0;
                                        $basePrice = $product->price ?? 0;
                                        $sellingPrice = $product->sale_price ?? $product->price ?? 0;
                                        $discount = 0;
                                        $discountPercent = 0;
                                        if ($product->sale_price && $product->sale_price < $basePrice) {
                                            $discount = $basePrice - $sellingPrice;
                                            $discountPercent = $basePrice > 0 ? round(($discount / $basePrice) * 100, 1) : 0;
                                        }
                                        $profitMargin = $sellingPrice - $costPrice;
                                        $marginPercent = $costPrice > 0 ? round(($profitMargin / $costPrice) * 100, 1) : 0;
                                        $stockValue = $totalStock * $sellingPrice;
                                        $costValue = $totalStock * $costPrice;
                                        $potentialProfit = $stockValue - $costValue;

                                        // Stock status based on calculated totalStock
                                        if ($totalStock > 10) {
                                            $statusClass = 'success';
                                            $statusText = 'In Stock';
                                        } elseif ($totalStock > 0) {
                                            $statusClass = 'warning';
                                            $statusText = 'Low Stock';
                                        } elseif ($totalStock == 0) {
                                            $statusClass = 'danger';
                                            $statusText = 'Out of Stock';
                                        } else {
                                            $statusClass = 'secondary';
                                            $statusText = 'Negative';
                                        }

                                        if ($marginPercent >= 50) $marginClass = 'success';
                                        elseif ($marginPercent >= 20) $marginClass = 'warning';
                                        elseif ($marginPercent >= 10) $marginClass = 'info';
                                        else $marginClass = 'danger';

                                        $discountClass = $discountPercent >= 20 ? 'danger' : ($discountPercent >= 10 ? 'warning' : 'info');
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="product-checkbox" value="{{ $product->id }}">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($product->thumbnail)
                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" class="rounded me-2" width="40" height="40" alt="{{ $product->title }}">
                                                @endif
                                                <div>
                                                    <div class="fw-semibold">{{ $product->title }}</div>
                                                    @if($product->barcode)
                                                        <small class="text-muted">Barcode: {{ $product->barcode }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $product->sku }}</td>
                                        <td>{{ $product->category?->name ?? '-' }}</td>
                                        <td>{{ $product->brand?->name ?? '-' }}</td>
                                        <td>
                                            @if($costPrice > 0)
                                                <span class="fw-bold text-info">₦{{ number_format($costPrice, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->sale_price && $product->sale_price < $basePrice)
                                                <del class="text-muted small">₦{{ number_format($basePrice, 2) }}</del>
                                            @else
                                                <span class="fw-bold">₦{{ number_format($basePrice, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->sale_price && $product->sale_price < $basePrice)
                                                <span class="fw-bold text-danger">₦{{ number_format($sellingPrice, 2) }}</span>
                                            @else
                                                <span class="fw-bold text-success">₦{{ number_format($sellingPrice, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($discountPercent > 0)
                                                <span class="badge bg-{{ $discountClass }}-subtle text-{{ $discountClass }} border border-{{ $discountClass }}-subtle">
                                                    -{{ $discountPercent }}%
                                                </span>
                                                <br>
                                                <small class="text-muted">Save ₦{{ number_format($discount, 2) }}</small>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary">No discount</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($profitMargin > 0)
                                                <span class="fw-bold text-primary">₦{{ number_format($profitMargin, 2) }}</span>
                                            @elseif($profitMargin == 0)
                                                <span class="text-muted">₦0.00</span>
                                            @else
                                                <span class="fw-bold text-danger">₦{{ number_format($profitMargin, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $marginClass }}-subtle text-{{ $marginClass }} border border-{{ $marginClass }}-subtle">
                                                {{ number_format($marginPercent, 1) }}%
                                            </span>
                                        </td>
                                        @foreach($locations as $location)
                                            @php
                                                $stock = $locationStockData[$product->id][$location->id] ?? 0;
                                                $stockClass = $stock > 10 ? 'success' : ($stock > 0 ? 'warning' : ($stock == 0 ? 'secondary' : 'danger'));
                                            @endphp
                                            <td class="text-center">
                                                <span class="badge bg-{{ $stockClass }}-subtle text-{{ $stockClass }} border border-{{ $stockClass }}-subtle">
                                                    {{ $stock }}
                                                </span>
                                            </td>
                                        @endforeach
                                        <td>
                                            <span class="fw-bold {{ $totalStock > 10 ? 'text-success' : ($totalStock > 0 ? 'text-warning' : ($totalStock == 0 ? 'text-secondary' : 'text-danger')) }}">
                                                {{ $totalStock }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-end">
                                                <div class="fw-bold {{ $product->sale_price && $product->sale_price < $basePrice ? 'text-danger' : 'text-success' }}">
                                                    ₦{{ number_format($stockValue, 2) }}
                                                </div>
                                                <small class="text-muted">Cost: ₦{{ number_format($costValue, 2) }}</small>
                                                <div class="small {{ $potentialProfit >= 0 ? 'text-primary' : 'text-danger' }}">
                                                    Profit: ₦{{ number_format($potentialProfit, 2) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} border border-{{ $statusClass }}-subtle">
                                                {{ $statusText }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-subtle-secondary btn-sm btn-icon" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="{{ route('web.products.show', $product->id) }}"><i class="bi bi-eye me-2"></i> View Product</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="showStockHistory({{ $product->id }})"><i class="bi bi-clock-history me-2"></i> View History</a></li>
                                                    @can('Manage inventory')
                                                        <li><a class="dropdown-item" href="#" onclick="quickAdjust({{ $product->id }}, '{{ addslashes($product->title) }}')"><i class="bi bi-plus-slash-minus me-2"></i> Adjust Stock</a></li>
                                                    @endcan
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 15 + count($locations) }}" class="text-center py-5 text-muted">
                                            <i class="bi bi-box-seam fs-1"></i>
                                            <p class="mt-2">No products found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-end">TOTALS:</th>
                                    <th>₦<span id="footerTotalBase">0.00</span></th>
                                    <th>₦<span id="footerTotalSelling">0.00</span></th>
                                    <th><span id="footerProductsOnSale">0</span> on sale</th>
                                    <th>₦<span id="footerTotalMargin">0.00</span></th>
                                    <th><span id="footerAvgMargin">0.0</span>%</th>
                                    @foreach($locations as $location)
                                        <th class="text-center">{{ $locationStockTotals[$location->id] ?? 0 }}</th>
                                    @endforeach
                                    <th><span id="footerTotalStock">0</span></th>
                                    <th>₦<span id="footerTotalValue">0.00</span></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="row mt-3 align-items-center">
                        <div class="col-sm">
                            <div class="text-muted text-center text-sm-start">
                                Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} Products
                            </div>
                        </div>
                        <div class="col-sm-auto mt-3 mt-sm-0">
                            {!! $products->appends(request()->query())->links('pagination::bootstrap-5') !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STOCK HISTORY MODAL -->
<div class="modal fade" id="stockHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="stockHistoryLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                </div>
                <div id="stockHistoryFilters" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" id="historySearch" class="form-control" placeholder="Reference, reason, notes, user...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" id="historyDateFrom" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" id="historyDateTo" class="form-control">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" id="applyHistoryFilters" class="btn btn-primary me-2">
                                <i class="bi bi-funnel"></i> Apply
                            </button>
                            <button type="button" id="resetHistoryFilters" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="stockHistoryContent"></div>
                <div id="historyPagination" class="mt-3 d-flex justify-content-center"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- QUICK ADJUST MODAL -->
<div class="modal fade" id="quickAdjustModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="quickAdjustForm">
                @csrf
                <input type="hidden" name="product_id" id="quickAdjustProductId">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickAdjustModalTitle">Quick Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="currentStockInfo" class="alert alert-info mb-3">
                        <i class="bi bi-info-circle me-1"></i> Select a location to see current stock
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location <span class="text-danger">*</span></label>
                        <select name="location_id" id="quickAdjustLocation" class="form-control" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->is_default ? 'selected' : '' }}>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" id="adjustmentType" class="form-control" required>
                            <option value="add">Add Stock</option>
                            <option value="remove">Remove Stock</option>
                            <option value="set">Set Stock to</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="quantityLabel">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="adjustmentQuantity" class="form-control" required min="0" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Cost (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" step="0.01" name="unit_cost" id="unitCost" class="form-control" placeholder="Leave empty to use product cost">
                        </div>
                        <small class="text-muted">Defaults to product cost price: ₦<span id="productCostPrice">0.00</span></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g., Restock, Damage, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
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

<!-- BULK ADJUST MODAL -->
<div class="modal fade" id="bulkAdjustModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="bulkAdjustForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Stock Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>{{ $products->total() }}</strong> products match current filters.
                        <span id="selectedCount">0</span> selected for adjustment.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location <span class="text-danger">*</span></label>
                        <select name="location_id" id="bulkLocation" class="form-control" required>
                            <option value="">Select Location</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ $location->is_default ? 'selected' : '' }}>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" id="bulkAdjustmentType" class="form-control" required>
                            <option value="add">Add Stock to All</option>
                            <option value="set">Set Stock to Same Value</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="bulkQuantityLabel">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="bulkQuantity" class="form-control" required min="0" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Cost (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" step="0.01" name="unit_cost" id="bulkUnitCost" class="form-control" placeholder="Leave empty to use product cost">
                        </div>
                        <small class="text-muted">Will use individual product cost prices if left empty</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g., Annual restock">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bulkAdjustBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="bulkSpinner"></span>
                        Apply to Selected (<span id="applyCount">0</span>)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let currentProductId = null;

// Calculate totals on page load and after filters
function calculateTotals() {
    let totalCostValue = 0;
    let totalSellingValue = 0;
    let totalPotentialProfit = 0;
    let totalStock = 0;
    let marginCount = 0;
    let marginPercentTotal = 0;
    let discountProducts = 0;
    let totalDiscountPercent = 0;

    const marginRanges = {
        'High (>50%)': 0,
        'Good (30-50%)': 0,
        'Average (20-30%)': 0,
        'Low (10-20%)': 0,
        'Very Low (<10%)': 0,
        'No Margin': 0,
        'Loss': 0
    };

    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length < 15) return;

        const costPriceText = cells[5].querySelector('.fw-bold')?.textContent?.replace('₦', '').replace(/,/g, '') ||
                            cells[5].textContent.replace('₦', '').replace(/,/g, '');
        const costPrice = parseFloat(costPriceText) || 0;

        // Check for discounted price (strikethrough)
        const basePriceCell = cells[6];
        const hasDiscount = basePriceCell.querySelector('del');
        let basePrice = 0;
        if (hasDiscount) {
            basePrice = parseFloat(hasDiscount.textContent?.replace('₦', '').replace(/,/g, '') || 0);
        } else {
            basePrice = parseFloat(basePriceCell.querySelector('.fw-bold')?.textContent?.replace('₦', '').replace(/,/g, '') || 0);
        }

        const sellingPrice = parseFloat(cells[7].querySelector('.fw-bold')?.textContent?.replace('₦', '').replace(/,/g, '') || 0);
        const marginPercentText = cells[10].querySelector('.badge')?.textContent?.replace('%', '') || '0';
        const marginPercent = parseFloat(marginPercentText) || 0;

        // Get total stock from the stock column (second last column before actions)
        const totalStockCell = cells[cells.length - 5];
        const stockText = totalStockCell.querySelector('.fw-bold')?.textContent || '0';
        const stock = parseInt(stockText.replace(/,/g, '')) || 0;

        // Get stock value from the value column
        const stockValueCell = cells[cells.length - 4];
        const stockValueText = stockValueCell.querySelector('.fw-bold')?.textContent?.replace('₦', '').replace(/,/g, '') || '0';
        const stockValue = parseFloat(stockValueText) || 0;

        const costValue = stock * costPrice;
        const sellingValue = stock * sellingPrice;
        const potentialProfit = sellingValue - costValue;

        totalCostValue += costValue;
        totalSellingValue += sellingValue;
        totalPotentialProfit += potentialProfit;
        totalStock += stock;

        if (costPrice > 0) {
            marginCount++;
            marginPercentTotal += marginPercent;
        }

        // Categorize margin
        if (marginPercent > 50) marginRanges['High (>50%)']++;
        else if (marginPercent >= 30) marginRanges['Good (30-50%)']++;
        else if (marginPercent >= 20) marginRanges['Average (20-30%)']++;
        else if (marginPercent >= 10) marginRanges['Low (10-20%)']++;
        else if (marginPercent > 0) marginRanges['Very Low (<10%)']++;
        else if (marginPercent === 0) marginRanges['No Margin']++;
        else marginRanges['Loss']++;
    });

    const avgMarginPercent = marginCount > 0 ? (marginPercentTotal / marginCount) : 0;

    document.getElementById('totalCostValue').textContent = formatNaira(totalCostValue);
    document.getElementById('totalSellingValue').textContent = formatNaira(totalSellingValue);
    document.getElementById('totalPotentialProfit').textContent = formatNaira(totalPotentialProfit);
    document.getElementById('avgMarginPercent').textContent = avgMarginPercent.toFixed(2);

    document.getElementById('footerTotalStock').textContent = totalStock.toLocaleString('en-NG');
    document.getElementById('footerTotalValue').textContent = formatNaira(totalSellingValue);

    updateMarginChart(marginRanges);
}

function formatNaira(amount) {
    return new Intl.NumberFormat('en-NG', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

function updateMarginChart(marginRanges) {
    const ctx = document.getElementById('marginDistributionChart');
    if (!ctx) return;
    const chart = Chart.getChart(ctx);
    if (chart) chart.destroy();
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(marginRanges),
            datasets: [{
                label: 'Number of Products',
                data: Object.values(marginRanges),
                backgroundColor: [
                    'rgba(25, 135, 84, 0.7)',
                    'rgba(13, 202, 240, 0.7)',
                    'rgba(255, 193, 7, 0.7)',
                    'rgba(253, 126, 20, 0.7)',
                    'rgba(108, 117, 125, 0.7)',
                    'rgba(32, 201, 151, 0.7)',
                    'rgba(220, 53, 69, 0.7)'
                ],
                borderColor: [
                    'rgba(25, 135, 84, 1)',
                    'rgba(13, 202, 240, 1)',
                    'rgba(255, 193, 7, 1)',
                    'rgba(253, 126, 20, 1)',
                    'rgba(108, 117, 125, 1)',
                    'rgba(32, 201, 151, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Number of Products' } },
                x: { title: { display: true, text: 'Margin Range' } }
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    calculateTotals();

    // Stock Status Chart
    const statusCtx = document.getElementById('stockStatusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [{{ $summary['in_stock'] }}, {{ $summary['low_stock'] }}, {{ $summary['out_of_stock'] }}],
                    backgroundColor: ['rgba(25, 135, 84, 0.8)', 'rgba(255, 193, 7, 0.8)', 'rgba(220, 53, 69, 0.8)'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '65%'
            }
        });
    }

    // Stock by Location Chart
    const locationCtx = document.getElementById('stockByLocationChart');
    if (locationCtx) {
        new Chart(locationCtx, {
            type: 'bar',
            data: {
                labels: [@foreach($locations as $location) "{{ $location->name }}", @endforeach],
                datasets: [{
                    label: 'Total Stock',
                    data: [@foreach($locations as $location) {{ $locationStockTotals[$location->id] ?? 0 }}, @endforeach],
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Stock Units' } }
                }
            }
        });
    }

    // Select All checkbox
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkCount();
    });

    document.querySelectorAll('.product-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkCount);
    });
});

function updateBulkCount() {
    const checked = document.querySelectorAll('.product-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = checked;
    document.getElementById('applyCount').textContent = checked;
}

function openBulkAdjustModal() {
    const checkedCount = document.querySelectorAll('.product-checkbox:checked').length;
    if (checkedCount === 0) {
        Swal.fire('Warning', 'Please select at least one product', 'warning');
        return;
    }
    new bootstrap.Modal(document.getElementById('bulkAdjustModal')).show();
}

document.getElementById('bulkAdjustForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('bulkAdjustBtn');
    const spinner = document.getElementById('bulkSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');
    const selected = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
    const formData = new FormData(this);
    formData.append('products', JSON.stringify(selected));
    axios.post('{{ route("inventory.bulk-adjust") }}', formData)
        .then(res => {
            if (res.data.success) {
                Swal.fire('Success!', res.data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', err.response?.data?.message || 'Failed', 'error'))
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
        });
});

function showStockHistory(productId) {
    currentProductId = productId;
    document.getElementById('historySearch').value = '';
    document.getElementById('historyDateFrom').value = '';
    document.getElementById('historyDateTo').value = '';
    loadStockHistory(productId);
}

function loadStockHistory(productId, page = 1) {
    const search = document.getElementById('historySearch')?.value || '';
    const from = document.getElementById('historyDateFrom')?.value || '';
    const to = document.getElementById('historyDateTo')?.value || '';
    const loading = document.getElementById('stockHistoryLoading');
    const content = document.getElementById('stockHistoryContent');
    const pagination = document.getElementById('historyPagination');
    loading.classList.remove('d-none');
    content.innerHTML = '';
    pagination.innerHTML = '';
    axios.get(`/inventory/history/${productId}`, { params: { page, search, date_from: from, date_to: to } })
        .then(res => {
            if (!res.data.success) throw new Error(res.data.message || 'Failed');
            const { product, history } = res.data;
            let html = `
                <h5 class="mb-1">${product.title} (${product.sku})</h5>
                <p class="text-muted mb-3">Current Stock: <strong>${product.stock ?? 'N/A'}</strong></p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered">
                        <thead class="table-light"><tr>
                            <th>Date</th><th>Type</th><th>Location</th><th>Qty</th><th>Unit Cost</th><th>Total Cost</th><th>Ref</th><th>User</th><th>Notes</th>
                        </tr></thead><tbody>`;
            if (history.data.length > 0) {
                const colors = { in: 'success', out: 'danger', adjustment: 'warning', transfer: 'info', transfer_in: 'info', return: 'primary', damage: 'dark' };
                history.data.forEach(t => {
                    const sign = ['in','adjustment','transfer_in','return'].includes(t.type) ? '+' : '-';
                    const user = t.user ? `${t.user.first_name} ${t.user.last_name}` : 'System';
                    const unitCost = t.unit_cost ? `₦${parseFloat(t.unit_cost).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : '-';
                    const totalCost = t.total_cost ? `₦${parseFloat(t.total_cost).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : '-';
                    html += `<tr>
                        <td class="small">${new Date(t.transaction_date).toLocaleString()}</td>
                        <td><span class="badge bg-${colors[t.type] || 'secondary'}">${t.type.replace('_',' ').toUpperCase()}</span></td>
                        <td class="small">${t.stock_location?.name || '-'}</td>
                        <td class="fw-bold ${sign==='+'?'text-success':'text-danger'}">${sign}${t.quantity}</td>
                        <td class="small">${unitCost}</td>
                        <td class="small">${totalCost}</td>
                        <td class="small">${t.reference_number || '-'}</td>
                        <td class="small">${user}</td>
                        <td class="small">${t.adjustment_reason || t.notes || '-'}</td>
                    </tr>`;
                });
            } else {
                html += `<tr><td colspan="9" class="text-center text-muted py-4">No transactions found</td></tr>`;
            }
            html += `</tbody></table></div>`;
            content.innerHTML = html;
            if (history.links) {
                let pag = '<nav><ul class="pagination pagination-sm">';
                history.links.forEach(link => {
                    if (!link.url) pag += `<li class="page-item disabled"><span class="page-link">${link.label}</span></li>`;
                    else {
                        const active = link.active ? 'active' : '';
                        const p = link.url.split('page=')[1] || 1;
                        pag += `<li class="page-item ${active}"><a class="page-link" href="#" data-page="${p}">${link.label}</a></li>`;
                    }
                });
                pag += '</ul></nav>';
                pagination.innerHTML = pag;
                document.querySelectorAll('#historyPagination a[data-page]').forEach(a => {
                    a.addEventListener('click', e => { e.preventDefault(); loadStockHistory(productId, a.dataset.page); });
                });
            }
            new bootstrap.Modal(document.getElementById('stockHistoryModal')).show();
        })
        .catch(err => {
            content.innerHTML = `<div class="alert alert-danger">${err.message || 'Failed to load history'}</div>`;
        })
        .finally(() => loading.classList.add('d-none'));
}

document.getElementById('applyHistoryFilters')?.addEventListener('click', () => currentProductId && loadStockHistory(currentProductId));
document.getElementById('resetHistoryFilters')?.addEventListener('click', () => {
    document.getElementById('historySearch').value = '';
    document.getElementById('historyDateFrom').value = '';
    document.getElementById('historyDateTo').value = '';
    currentProductId && loadStockHistory(currentProductId);
});

['historySearch', 'historyDateFrom', 'historyDateTo'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        clearTimeout(window.historySearchTimeout);
        window.historySearchTimeout = setTimeout(() => currentProductId && loadStockHistory(currentProductId), 600);
    });
});

function quickAdjust(productId, productTitle) {
    document.getElementById('quickAdjustProductId').value = productId;
    document.getElementById('quickAdjustModalTitle').textContent = 'Quick Adjustment - ' + productTitle;
    document.getElementById('quickAdjustForm').reset();
    document.getElementById('adjustmentQuantity').value = 1;
    document.getElementById('adjustmentType').value = 'add';
    document.getElementById('unitCost').value = '';
    const row = document.querySelector(`.product-checkbox[value="${productId}"]`)?.closest('tr');
    if (row) {
        const costCell = row.querySelectorAll('td')[5];
        const costPrice = costCell.querySelector('.fw-bold')?.textContent?.replace('₦', '').replace(/,/g, '') || costCell.textContent.replace('₦', '').replace(/,/g, '');
        document.getElementById('productCostPrice').textContent = costPrice ? parseFloat(costPrice).toLocaleString('en-NG', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';
    }
    updateQuantityLabel();
    document.getElementById('currentStockInfo').innerHTML = '<i class="bi bi-info-circle"></i> Select location to see current stock';
    const modal = new bootstrap.Modal(document.getElementById('quickAdjustModal'));
    modal.show();
    const loc = document.getElementById('quickAdjustLocation');
    if (loc.value) loadCurrentStock(productId, loc.value);
}

function loadCurrentStock(pid, lid) {
    axios.get(`/inventory/stock-level/${pid}/${lid}`).then(r => {
        if (r.data.success) {
            document.getElementById('currentStockInfo').innerHTML = `<i class="bi bi-info-circle"></i> Current: <strong>${r.data.stock || 0}</strong>`;
        }
    });
}

function updateQuantityLabel() {
    const type = document.getElementById('adjustmentType').value;
    const label = document.getElementById('quantityLabel');
    const input = document.getElementById('adjustmentQuantity');
    if (type === 'add') { label.textContent = 'Quantity to Add *'; input.min = 1; }
    else if (type === 'remove') { label.textContent = 'Quantity to Remove *'; input.min = 1; }
    else { label.textContent = 'Set Stock To *'; input.min = 0; }
    if (input.value < input.min) input.value = input.min;
}

document.getElementById('adjustmentType')?.addEventListener('change', updateQuantityLabel);
document.getElementById('bulkAdjustmentType')?.addEventListener('change', function() {
    const label = document.getElementById('bulkQuantityLabel');
    if (this.value === 'add') {
        label.textContent = 'Quantity to Add *';
    } else {
        label.textContent = 'Set Stock To *';
    }
});

document.getElementById('quickAdjustForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('quickAdjustBtn');
    const spin = document.getElementById('quickAdjustSpinner');
    btn.disabled = true; spin.classList.remove('d-none');
    axios.post('{{ route("inventory.adjust") }}', new FormData(this))
        .then(r => {
            if (r.data.success) {
                Swal.fire('Success!', r.data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', r.data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', err.response?.data?.message || 'Failed', 'error'))
        .finally(() => { btn.disabled = false; spin.classList.add('d-none'); });
});
</script>
@endsection
