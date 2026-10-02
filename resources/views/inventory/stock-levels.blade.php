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

            <!-- STOCK LEVELS (server-side DataTable) -->
            <x-cb.card title="Stock Levels by Location" icon="ri-stack-line" :flush="true" class="mt-4">
                <x-slot:tools>
                    @can('Manage inventory')
                        <button type="button" class="btn btn-sm btn-info" onclick="openBulkAdjustModal()"><i class="bi bi-plus-slash-minus me-1"></i> Bulk Adjust (<span id="selectedCountBadge">0</span>)</button>
                    @endcan
                    <button type="button" class="btn btn-sm btn-success" onclick="exportStock('csv')"><i class="bi bi-download me-1"></i> Export CSV</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="exportStock('pdf')"><i class="bi bi-file-earmark-pdf me-1"></i> Export PDF</button>
                </x-slot:tools>

                <div class="gz-filter-bar px-3 pt-3" id="stockLevelsForm">
                    <select id="f-stock_status" class="form-select form-select-sm" data-dt-filter="#stockLevelsTable">
                        <option value="">All Status</option>
                        <option value="in_stock" @selected(request('stock_status') == 'in_stock')>In Stock (&gt;10)</option>
                        <option value="low_stock" @selected(request('stock_status') == 'low_stock')>Low Stock (1-10)</option>
                        <option value="out_of_stock" @selected(request('stock_status') == 'out_of_stock')>Out of Stock</option>
                    </select>
                    <select id="f-category_id" class="form-select form-select-sm" data-dt-filter="#stockLevelsTable">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select id="f-brand_id" class="form-select form-select-sm" data-dt-filter="#stockLevelsTable">
                        <option value="">All Brands</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted ms-auto">Value cards and the margin chart reflect the filtered products.</small>
                </div>

                <div class="px-3 pb-3 gz-dt-wrap">
                    <table class="table gz-dt align-middle w-100 mb-0" id="stockLevelsTable">
                        <thead>
                            <tr>
                                <th style="width:36px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
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
                        <tbody></tbody>
                    </table>
                </div>
            </x-cb.card>
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
                        <strong id="matchCount">0</strong> products match current filters.
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

// Totals for the value cards come from the DataTable response (whole filtered set)
function applyTotals(t) {
    if (!t) return;
    document.getElementById('totalCostValue').textContent = formatNaira(t.cost_value);
    document.getElementById('totalSellingValue').textContent = formatNaira(t.selling_value);
    document.getElementById('totalPotentialProfit').textContent = formatNaira(t.profit);
    document.getElementById('avgMarginPercent').textContent = Number(t.avg_margin || 0).toFixed(2);
    updateMarginChart(t.margin_ranges || {});
    var mc = document.getElementById('matchCount'); if (mc) mc.textContent = t.products;
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

var STOCK_URLS = {
    data: @json(route('inventory.stock-levels.data')),
    csv:  @json(route('inventory.export.stock-levels')),
    pdf:  @json(route('inventory.export.stock-levels.pdf'))
};
function stockFilters() {
    return { stock_status: $('#f-stock_status').val(), category_id: $('#f-category_id').val(), brand_id: $('#f-brand_id').val() };
}
function exportStock(kind) {
    var f = stockFilters(), q = new URLSearchParams();
    Object.keys(f).forEach(function (k) { if (f[k]) q.append(k, f[k]); });
    if (kind === 'pdf') { window.open(STOCK_URLS.pdf + '?' + q.toString(), '_blank'); } else { window.location = STOCK_URLS.csv + '?' + q.toString(); }
}

document.addEventListener('DOMContentLoaded', function () {
    var cols = [
        { data: 'checkbox',   name: 'checkbox', orderable: false, searchable: false },
        { data: 'product',    name: 'product' },
        { data: 'sku',        name: 'products.sku' },
        { data: 'category',   name: 'category' },
        { data: 'brand',      name: 'brand' },
        { data: 'cost_price', name: 'products.cost_price', searchable: false },
        { data: 'price',      name: 'products.price', searchable: false },
        { data: 'selling',    name: 'selling', searchable: false },
        { data: 'discount',   name: 'discount', orderable: false, searchable: false },
        { data: 'profit',     name: 'profit', orderable: false, searchable: false },
        { data: 'margin',     name: 'margin', orderable: false, searchable: false }
    ];
    @foreach($locations as $location)
    cols.push({ data: 'loc_{{ (int) $location->id }}', name: 'loc_{{ (int) $location->id }}', searchable: false, className: 'text-center' });
    @endforeach
    cols.push(
        { data: 'total_stock', name: 'total_stock', searchable: false },
        { data: 'stock_value', name: 'stock_value', orderable: false, searchable: false },
        { data: 'status',      name: 'status', orderable: false, searchable: false },
        { data: 'action',      name: 'action', orderable: false, searchable: false }
    );

    window.stockLevelsTable = GZ.dt('#stockLevelsTable', {
        url: STOCK_URLS.data,
        order: [[1, 'asc']],
        filters: stockFilters,
        columns: cols,
        onDraw: function (settings) {
            if (settings.json) { applyTotals(settings.json.totals); }
            var all = document.getElementById('selectAll'); if (all) all.checked = false;
            updateBulkCount();
        }
    });
    var initialSearch = @json(request('search', ''));
    if (initialSearch) { window.stockLevelsTable.search(initialSearch).draw(); }

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

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('product-checkbox')) { updateBulkCount(); }
    });
});

function updateBulkCount() {
    const checked = document.querySelectorAll('.product-checkbox:checked').length;
    ['selectedCount', 'applyCount', 'selectedCountBadge'].forEach(function (id) {
        var el = document.getElementById(id); if (el) el.textContent = checked;
    });
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
