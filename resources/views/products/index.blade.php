@extends('layouts.master')

@section('title', 'Products Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Product Management' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0)">Ecommerce</a></li>
                                <li class="breadcrumb-item active">Products</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ANALYTICS DASHBOARD -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-primary-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-primary mb-0">Total Products</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($analytics['total_products'] ?? 0) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary rounded-circle fs-3">
                                        <i class="bi bi-box-seam"></i>
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
                    <div class="card card-animate bg-warning-subtle border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-warning mb-0">Featured Products</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ $analytics['featured_count'] ?? 0 }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning rounded-circle fs-3">
                                        <i class="bi bi-star-fill text-white"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'bg-danger-subtle' : 'bg-info-subtle' }} border-0">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'text-danger' : 'text-info' }} mb-0">Low Stock Alert</p>
                                    <h4 class="fs-22 fw-semibold mb-0 {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'text-danger' : 'text-info' }}">{{ $analytics['low_stock_count'] ?? 0 }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title {{ ($analytics['low_stock_count'] ?? 0) > 0 ? 'bg-danger' : 'bg-info' }} rounded-circle fs-3">
                                        <i class="bi bi-exclamation-triangle-fill text-white"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS & TOP PRODUCTS -->
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
                            <h5 class="card-title mb-0">Top Selling Products</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <tbody>
                                        @forelse($analytics['top_products'] ?? [] as $item)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-xs me-3">
                                                            <img src="{{ $item->thumbnail ? asset('storage/'.$item->thumbnail) : asset('img/no-image.png') }}" class="rounded-circle avatar-xs">
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ Str::limit($item->title, 30) }}</h6>
                                                            <small class="text-muted">{{ $item->sold_quantity }} sold</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-success-subtle text-success">${{ number_format($item->total_sales ?? 0, 2) }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="text-center text-muted py-4">No sales yet</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT LIST -->
            <div id="productList" class="mt-4">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Products <span class="badge bg-dark-subtle text-dark ms-1" id="totalProducts">{{ $products->total() }}</span>
                                        <small class="text-muted ms-2">Drag to reorder</small>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()">Delete Selected</button>
                                        <button type="button" class="btn btn-warning d-none" id="bulkEditBtn" data-bs-toggle="modal" data-bs-target="#bulkEditModal">Bulk Edit</button>
                                        @can('Create product')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal" onclick="resetForm()">Add Product</button>
                                        @endcan
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">Import CSV</button>
                                        <a href="{{ route('products.export') }}" class="btn btn-info">Export CSV</a>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th></th>
                                                <th><div class="form-check"><input class="form-check-input" type="checkbox" id="checkAll"><label class="form-check-label" for="checkAll"></label></div></th>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Stock</th>
                                                <th>Price</th>
                                                <th>Sold</th>
                                                <th>Featured</th>
                                                <th>Published</th>
                                                <th>Inventory</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all" id="sortableTableBody">
                                            @forelse($products as $product)
                                                <tr data-product-id="{{ $product->id }}" class="draggable-row">
                                                    <td class="text-center cursor-move"><i class="bi bi-grip-vertical text-muted fs-4"></i></td>
                                                    <td><div class="form-check"><input class="form-check-input bulk-checkbox" type="checkbox" value="{{ $product->id }}"></div></td>
                                                    <td class="title">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-light rounded p-1 me-3">
                                                                @if($product->thumbnail)
                                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="" class="img-fluid rounded" style="max-height:40px;">
                                                                @else
                                                                    <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center" style="width:40px;height:40px;"><i class="bi bi-image text-muted fs-5"></i></div>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><a href="{{ route('products.show', $product->id) }}" class="text-reset">{{ Str::limit($product->title, 50) }}</a></h6>
                                                                <p class="mb-0 text-muted small">SKU: {{ $product->sku }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="category">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                                    <!-- FIXED: Added id and data-current-stock attributes -->
                                                   <td class="stock" id="stock-{{ $product->id }}" data-current-stock="{{ $product->stock }}">
    @if($product->stock > 10)
        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">{{ $product->stock }} in stock</span>
    @elseif($product->stock > 0)
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $product->stock }} low stock</span>
    @else
        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">Out of stock</span>
    @endif
</td>
                                                    <td class="price">
                                                        @if($product->sale_price && $product->sale_price < $product->price)
                                                            @php $discount = round((($product->price - $product->sale_price) / $product->price) * 100); @endphp
                                                            <div class="position-relative d-inline-block">
                                                                <del class="text-muted small">${{ number_format($product->price, 2) }}</del><br>
                                                                <span class="text-danger fw-bold">${{ number_format($product->sale_price, 2) }}</span>
                                                                <span class="badge bg-danger position-absolute" style="top:-8px;right:-32px;font-size:0.65rem;">-{{ $discount }}%</span>
                                                            </div>
                                                        @else
                                                            <span class="fw-bold">${{ number_format($product->price, 2) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="sold text-center"><span class="fw-semibold">{{ $product->sold_quantity ?? 0 }}</span></td>
                                                    <td class="featured">
                                                        @if($product->is_featured)
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                                <i class="bi bi-star-fill text-warning me-1"></i> Featured
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Regular</span>
                                                        @endif
                                                    </td>
                                                    <td class="created_at"><small class="text-muted">{{ $product->created_at->format('d M, Y') }}</small></td>
                                                    <td><button type="button" class="btn btn-sm btn-outline-info inventory-btn" data-id="{{ $product->id }}" data-title="{{ $product->title }}">View Log</button></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item" href="{{ route('products.show', $product->id) }}">View</a></li>
                                                                @can('Update product')
                                                                    <li><a class="dropdown-item edit-item-btn" href="javascript:void(0);" data-id="{{ $product->id }}">Edit</a></li>
                                                                @endcan
                                                                @can('Delete product')
                                                                    <li><a class="dropdown-item remove-item-btn text-danger" href="javascript:void(0);" data-id="{{ $product->id }}">Delete</a></li>
                                                                @endcan
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="11" class="text-center py-5 text-muted">No products found</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3 align-items-center">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} Results
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

            <!-- ADD/EDIT MODAL -->
            <div class="modal fade" id="showModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <form id="productForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" id="product_id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTitle">Add Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetForm()"></button>
                            </div>
                            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                                <div class="row g-4">
                                    <div class="col-lg-8">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title mb-3">Basic Information</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-6"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" name="title" id="title" class="form-control" required></div>
                                                    <div class="col-md-6"><label class="form-label">SKU <span class="text-danger">*</span></label><input type="text" name="sku" id="sku" class="form-control" required></div>
                                                    <div class="col-md-4"><label class="form-label">Price <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.01" name="price" id="price" class="form-control" required min="0"></div></div>
                                                    <div class="col-md-4"><label class="form-label">Discount %</label><input type="number" step="0.01" min="0" max="100" id="discount_percent" class="form-control" placeholder="e.g. 25"></div>
                                                    <div class="col-md-4"><label class="form-label">Sale Price</label><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control"></div><small class="text-success" id="sale_price_note" style="display:none;">Auto-calculated</small></div>
                                                    <div class="col-md-4"><label class="form-label">Stock <span class="text-danger">*</span></label><input type="number" name="stock" id="stock" class="form-control" required min="0"></div>
                                                    <div class="col-md-6"><label class="form-label">Product Type <span class="text-danger">*</span></label><select name="product_type" id="product_type" class="form-control" required><option value="simple">Simple Product</option><option value="variable">Variable Product</option></select></div>
                                                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" id="description" rows="4" class="form-control"></textarea></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="variationsSection" style="display:none;">
                                            <div class="card mt-4">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <h6 class="mb-0">Product Variations</h6>
                                                    <div>
                                                        <input type="number" step="0.01" min="0" max="100" id="bulk_discount" class="form-control form-control-sm d-inline-block w-auto me-2" placeholder="Bulk %">
                                                        <button type="button" class="btn btn-success btn-sm" id="applyBulkDiscount">Apply to All</button>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div id="attributesContainer" class="mb-4">
                                                        <div class="row g-3 align-items-end attribute-row">
                                                            <div class="col-md-5"><input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]"></div>
                                                            <div class="col-md-6"><input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]"></div>
                                                            <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addAttribute">+ Add Attribute</button>
                                                    <button type="button" class="btn btn-primary btn-sm mb-3" id="generateVariations">Generate Variations</button>
                                                    <hr>
                                                    <div id="variationsTable"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label class="form-label">Thumbnail Image</label>
                                                <input type="file" name="thumbnail" id="thumbnail_input" class="form-control mb-2" accept="image/*">
                                                <div class="text-center">
                                                    <img id="thumbnail_preview" src="" class="img-fluid rounded" style="max-height:200px; display:none;">
                                                    <div id="thumbnail_placeholder" class="text-muted"><i class="bi bi-image display-4"></i><p class="mt-2">No image selected</p></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label class="form-label">Gallery Images</label>
                                                <input type="file" name="images[]" id="gallery_input" multiple class="form-control mb-3" accept="image/*">
                                                <div id="imageGallery" class="row g-2"></div>
                                            </div>
                                        </div>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Brand</label>
                                                    <select name="brand_id" id="brand_id" class="form-control">
                                                        <option value="">No Brand</option>
                                                        @foreach($brands as $brand)
                                                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label">Category</label>
                                                    <select name="category_id" id="category_id" class="form-control">
                                                        <option value="">Select Category</option>
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                            @foreach($cat->children as $child)
                                                                <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                                            @endforeach
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                                    <label class="form-check-label" for="is_featured">Featured Product</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top pt-3 bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>
                                    Save Product
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- IMPORT MODAL -->
            <div class="modal fade" id="importModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Import Products</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="importForm" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Choose CSV File</label>
                                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx" required>
                                    <small class="text-muted">Download <a href="{{ asset('samples/products_sample.csv') }}" class="text-primary">sample template</a></small>
                                </div>
                                <div class="progress mb-3" style="display:none;" id="importProgress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%">0%</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success">Start Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- BULK EDIT MODAL -->
            <div class="modal fade" id="bulkEditModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Bulk Edit (<span id="bulkCount">0</span> Products)</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="bulkEditForm">
                            @csrf
                            <input type="hidden" name="ids" id="bulk_product_ids">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Price Adjustment</label>
                                    <div class="input-group">
                                        <select name="price_action" class="form-control">
                                            <option value="">No Change</option>
                                            <option value="increase">Increase by</option>
                                            <option value="decrease">Decrease by</option>
                                            <option value="set">Set to</option>
                                        </select>
                                        <input type="number" step="0.01" name="price_value" class="form-control" placeholder="Value">
                                        <span class="input-group-text">% / $</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stock Adjustment</label>
                                    <div class="input-group">
                                        <select name="stock_action" class="form-control">
                                            <option value="">No Change</option>
                                            <option value="increase">Increase by</option>
                                            <option value="decrease">Decrease by</option>
                                            <option value="set">Set to</option>
                                        </select>
                                        <input type="number" name="stock_value" class="form-control" placeholder="Quantity">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-control">
                                        <option value="">No Change</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @foreach($cat->children as $child)
                                                <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Featured Status</label>
                                    <select name="is_featured" class="form-control">
                                        <option value="">No Change</option>
                                        <option value="1">Mark as Featured</option>
                                        <option value="0">Remove Featured</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Apply Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- INVENTORY LOG MODAL -->
            <div class="modal fade" id="inventoryModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Inventory Log - <span id="inventoryTitle"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Type</th>
                                            <th>Quantity</th>
                                            <th>Reference</th>
                                            <th>Previous Stock</th>
                                            <th>New Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventoryLogBody">
                                        <!-- Dynamic content will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- COMPLETE JAVASCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Set CSRF token for all axios requests
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

    // Initialize Sales Chart
    const salesChartCtx = document.getElementById('salesChart');
    if (salesChartCtx) {
        new Chart(salesChartCtx, {
            type: 'line',
            data: {
                labels: @json($analytics['sales_chart']['labels'] ?? []),
                datasets: [{
                    label: 'Daily Sales ($)',
                    data: @json($analytics['sales_chart']['data'] ?? []),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: '#495057' }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#6c757d' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            color: '#6c757d',
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
    }

    // Initialize List.js for product filtering
    const productListOptions = {
        valueNames: ['title', 'category', 'stock', 'price', 'sold', 'created_at', 'featured'],
        page: 15,
        pagination: true
    };
    
    if (document.getElementById('productList')) {
        new List('productList', productListOptions);
    }

    // Initialize drag and drop for product reordering
    const tbody = document.getElementById('sortableTableBody');
    if (tbody) {
        new Sortable(tbody, {
            animation: 150,
            ghostClass: 'bg-light',
            handle: '.cursor-move',
            filter: '.ignore-drag',
            preventOnFilter: false,
            onEnd: function () {
                const items = Array.from(tbody.querySelectorAll('.draggable-row'));
                const newOrder = items.map((row, i) => ({
                    id: row.dataset.productId,
                    position: i + 1
                }));
                
                axios.post('/products/reorder', { order: newOrder })
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Saved!',
                            text: 'Product order has been updated successfully.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    })
                    .catch((error) => {
                        console.error('Reorder error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to save product order. Please try again.',
                            timer: 2000
                        });
                        location.reload();
                    });
            }
        });
    }

    // Bulk selection functionality
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.bulk-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkActions();
        });
    }

    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.bulk-checkbox:checked');
        const count = checkedBoxes.length;
        
        const removeActionsBtn = document.getElementById('remove-actions');
        const bulkEditBtn = document.getElementById('bulkEditBtn');
        const bulkCountSpan = document.getElementById('bulkCount');
        const bulkProductIdsInput = document.getElementById('bulk_product_ids');
        
        if (removeActionsBtn) removeActionsBtn.classList.toggle('d-none', count === 0);
        if (bulkEditBtn) bulkEditBtn.classList.toggle('d-none', count === 0);
        if (bulkCountSpan) bulkCountSpan.textContent = count;
        if (bulkProductIdsInput) {
            bulkProductIdsInput.value = Array.from(checkedBoxes).map(cb => cb.value).join(',');
        }
    }

    // Attach change event to all bulk checkboxes
    document.querySelectorAll('.bulk-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    // Delete multiple products
    window.deleteMultiple = function () {
        const ids = Array.from(document.querySelectorAll('.bulk-checkbox:checked')).map(cb => cb.value);
        if (!ids.length) {
            Swal.fire('Info', 'Please select products to delete.', 'info');
            return;
        }
        
        Swal.fire({
            title: `Delete ${ids.length} product(s)?`,
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Delete products one by one
                const deletePromises = ids.map(id => axios.delete(`/products/${id}`));
                Promise.all(deletePromises)
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: `${ids.length} product(s) have been deleted.`,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    })
                    .catch(error => {
                        console.error('Delete error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete some products. Please try again.'
                        });
                    });
            }
        });
    };

    // Initialize Choices.js for select elements
    if (typeof Choices !== 'undefined') {
        const brandSelect = document.getElementById('brand_id');
        if (brandSelect) {
            new Choices(brandSelect, {
                removeItemButton: true,
                searchEnabled: true,
                placeholderValue: 'Select a brand',
                searchPlaceholderValue: 'Search brands...'
            });
        }
        
        const categorySelect = document.getElementById('category_id');
        if (categorySelect) {
            new Choices(categorySelect, {
                removeItemButton: true,
                searchEnabled: true,
                placeholderValue: 'Select a category',
                searchPlaceholderValue: 'Search categories...'
            });
        }
    }

 // ============ REAL-TIME STOCK UPDATES ============

// Function to update stock display for a single product
function updateStockDisplay(productId, newStock) {
    const cell = document.querySelector(`#stock-${productId}`);
    if (!cell) {
        console.log(`Stock cell not found for product ${productId}`);
        return;
    }
    
    const currentStock = parseInt(cell.getAttribute('data-current-stock') || 0);
    
    // Only update if stock has changed
    if (currentStock !== newStock) {
        console.log(`Updating stock for product ${productId}: ${currentStock} → ${newStock}`);
        
        let badgeClass, text;
        if (newStock > 10) {
            badgeClass = 'bg-success-subtle text-success-emphasis border border-success-subtle';
            text = `${newStock} in stock`;
        } else if (newStock > 0) {
            badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
            text = `${newStock} low stock`;
        } else {
            badgeClass = 'bg-danger-subtle text-danger-emphasis border border-danger-subtle';
            text = 'Out of stock';
        }
        
        // Update the display
        cell.innerHTML = `<span class="badge ${badgeClass}">${text}</span>`;
        
        // Update the data attribute
        cell.setAttribute('data-current-stock', newStock);
        
        // Highlight change with animation
        cell.style.transition = 'all 0.3s';
        
        if (newStock > currentStock) {
            // Stock increased - green highlight
            cell.style.backgroundColor = '#d4edda';
            const badge = cell.querySelector('.badge');
            if (badge) {
                badge.style.animation = 'pulse 0.5s';
            }
        } else if (newStock < currentStock) {
            // Stock decreased - red highlight
            cell.style.backgroundColor = '#f8d7da';
            const badge = cell.querySelector('.badge');
            if (badge) {
                badge.style.animation = 'pulse 0.5s';
            }
        }
        
        // Remove highlight after 1 second
        setTimeout(() => {
            cell.style.backgroundColor = '';
        }, 1000);
    }
}

// Function to fetch and update all product stocks
function fetchRealTimeStock() {
    console.log('Fetching real-time stock updates...');
    axios.get('/inventory/realtime-product-stock')
        .then(response => {
            if (response.data && Array.isArray(response.data)) {
                console.log(`Received ${response.data.length} product stock updates`);
                response.data.forEach(item => {
                    updateStockDisplay(item.id, item.stock);
                });
            } else {
                console.log('No stock data received or invalid format');
            }
        })
        .catch(error => {
            console.error('Real-time stock update error:', error);
            // Check if route exists
            if (error.response && error.response.status === 404) {
                console.error('Route /inventory/realtime-product-stock not found!');
                console.error('Please add this route to routes/web.php:');
                console.error("Route::get('/inventory/realtime-product-stock', [InventoryController::class, 'realtimeProductStock']);");
            }
        });
}

// Start polling every 5 seconds (5000ms)
let stockPollingInterval = setInterval(fetchRealTimeStock, 5000);

// Initial fetch after 1 second
setTimeout(fetchRealTimeStock, 1000);

// Add CSS animation for pulse effect
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    .stock-updated {
        animation: pulse 0.5s ease-in-out;
    }
`;
document.head.appendChild(style);

    // ============ EVENT DELEGATION FOR DYNAMIC ELEMENTS ============

    document.addEventListener('click', function(e) {
        // Inventory button click
        if (e.target.classList.contains('inventory-btn')) {
            const id = e.target.dataset.id;
            const title = e.target.dataset.title;
            document.getElementById('inventoryTitle').textContent = title;
            
            axios.get(`/products/${id}/inventory`)
                .then(response => {
                    const tbody = document.getElementById('inventoryLogBody');
                    if (response.data && response.data.length > 0) {
                        tbody.innerHTML = response.data.map(log => `
                            <tr>
                                <td>${new Date(log.created_at).toLocaleString()}</td>
                                <td>
                                    <span class="badge bg-${log.type === 'in' ? 'success' : 'danger'}">
                                        ${log.type === 'in' ? 'Added' : 'Removed'}
                                    </span>
                                </td>
                                <td><strong>${log.quantity}</strong></td>
                                <td>${log.reference || '-'}</td>
                                <td>${log.previous_stock}</td>
                                <td><strong>${log.new_stock}</strong></td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No inventory records found</td></tr>';
                    }
                    
                    // Show the inventory modal
                    const inventoryModal = new bootstrap.Modal(document.getElementById('inventoryModal'));
                    inventoryModal.show();
                })
                .catch(error => {
                    console.error('Inventory load error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load inventory data. Please try again.'
                    });
                });
        }

        // Edit button click
        if (e.target.closest('.edit-item-btn')) {
            const btn = e.target.closest('.edit-item-btn');
            const id = btn.dataset.id;
            
            // Show loading state
            Swal.fire({
                title: 'Loading product data...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            axios.get(`/products/${id}/edit`)
                .then(response => {
                    Swal.close();
                    const product = response.data;

                    // Reset form first
                    resetForm();
                    
                    // Fill form with product data
                    document.getElementById('product_id').value = product.id;
                    document.getElementById('title').value = product.title || '';
                    document.getElementById('sku').value = product.sku || '';
                    document.getElementById('price').value = product.price || '';
                    document.getElementById('sale_price').value = product.sale_price || '';
                    document.getElementById('stock').value = product.stock || '';
                    document.getElementById('description').value = product.description || '';
                    document.getElementById('brand_id').value = product.brand_id || '';
                    document.getElementById('category_id').value = product.category_id || '';
                    document.getElementById('product_type').value = product.product_type || 'simple';
                    document.getElementById('is_featured').checked = !!product.is_featured;

                    // Handle thumbnail image
                    const thumbPreview = document.getElementById('thumbnail_preview');
                    const thumbPlaceholder = document.getElementById('thumbnail_placeholder');
                    if (product.thumbnail) {
                        const thumbnailUrl = product.thumbnail.includes('http') ? product.thumbnail : `/storage/${product.thumbnail}`;
                        thumbPreview.src = thumbnailUrl;
                        thumbPreview.style.display = 'block';
                        thumbPlaceholder.style.display = 'none';
                    } else {
                        thumbPreview.style.display = 'none';
                        thumbPlaceholder.style.display = 'block';
                    }

                    // Handle gallery images
                    const gallery = document.getElementById('imageGallery');
                    gallery.innerHTML = '';
                    if (product.gallery && product.gallery.length > 0) {
                        product.gallery.forEach((img, index) => {
                            const div = document.createElement('div');
                            div.className = 'col-6 col-md-4 position-relative';
                            const imgUrl = img.url || img;
                            div.innerHTML = `
                                <img src="${imgUrl}" class="img-fluid rounded" style="height:100px;object-fit:cover;">
                                <input type="hidden" name="existing_images[]" value="${imgUrl}">
                                <button type="button" class="btn-close position-absolute top-0 end-0 bg-white" onclick="this.parentElement.remove()"></button>
                            `;
                            gallery.appendChild(div);
                        });
                    }

                    // Handle sale price calculation
                    calculateFromSalePrice();

                    // Handle variations for variable products
                    const variationsSection = document.getElementById('variationsSection');
                    if (product.product_type === 'variable' && product.variations && product.variations.length > 0) {
                        variationsSection.style.display = 'block';
                        
                        // Clear existing attributes
                        const container = document.getElementById('attributesContainer');
                        container.innerHTML = '';
                        
                        // Add attributes if they exist
                        if (product.attributes && product.attributes.length > 0) {
                            product.attributes.forEach((attr, index) => {
                                const attrHtml = `
                                    <div class="row g-3 align-items-end attribute-row ${index > 0 ? 'mt-3' : ''}">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" placeholder="e.g. Color" 
                                                   name="attributes[${index}][name]" value="${attr.name}">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" placeholder="Red, Blue, Green" 
                                                   name="attributes[${index}][values]" value="${attr.values ? attr.values.join(', ') : ''}">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button>
                                        </div>
                                    </div>
                                `;
                                container.insertAdjacentHTML('beforeend', attrHtml);
                            });
                            attrIndex = product.attributes.length;
                        } else {
                            // Add default attribute row
                            const defaultRow = `
                                <div class="row g-3 align-items-end attribute-row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button>
                                    </div>
                                </div>
                            `;
                            container.innerHTML = defaultRow;
                            attrIndex = 1;
                        }

                        // Generate variations table
                        let variationsHtml = `
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Variant</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Sale Price</th>
                                        <th>Stock</th>
                                        <th>Image</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>`;
                        
                        product.variations.forEach((variation, index) => {
                            const name = Object.entries(variation.attributes || {}).map(([key, value]) => `${key}: ${value}`).join(' | ') || 'Default';
                            const hiddenInputs = Object.entries(variation.attributes || {}).map(([key, value]) => 
                                `<input type="hidden" name="variations[${index}][attributes][${key}]" value="${value}">`
                            ).join('');
                            
                            variationsHtml += `
                                <tr>
                                    <td>
                                        <small>${name}</small>
                                        ${hiddenInputs}
                                        <input type="hidden" name="variations[${index}][id]" value="${variation.id || ''}">
                                    </td>
                                    <td>
                                        <input type="text" name="variations[${index}][sku]" value="${variation.sku || ''}" 
                                               class="form-control form-control-sm" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="variations[${index}][price]" value="${variation.price}" 
                                               class="form-control form-control-sm" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="variations[${index}][sale_price]" value="${variation.sale_price || ''}" 
                                               class="form-control form-control-sm">
                                    </td>
                                    <td>
                                        <input type="number" name="variations[${index}][stock]" value="${variation.stock}" 
                                               class="form-control form-control-sm" required>
                                    </td>
                                    <td>
                                        <input type="file" name="variations[${index}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                                        ${variation.image ? `<img src="${variation.image}" class="variation-preview mt-2 img-fluid rounded" style="max-height:80px;">` : ''}
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-variation">Remove</button>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        variationsHtml += `</tbody></table>`;
                        document.getElementById('variationsTable').innerHTML = variationsHtml;
                    } else {
                        variationsSection.style.display = 'none';
                    }

                    // Update modal title and button
                    document.getElementById('modalTitle').textContent = 'Edit Product';
                    document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span> Update Product';
                    
                    // Show the modal
                    const modalElement = document.getElementById('showModal');
                    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modal.show();
                    
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error loading product:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load product data. Please try again.'
                    });
                });
        }

        // Delete button click
        if (e.target.closest('.remove-item-btn')) {
            const id = e.target.closest('.remove-item-btn').dataset.id;
            Swal.fire({
                title: 'Are you sure?',
                text: "This product will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`/products/${id}`)
                        .then(() => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Product has been deleted successfully.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        })
                        .catch(error => {
                            console.error('Delete error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to delete product. Please try again.'
                            });
                        });
                }
            });
        }
    });

    // ============ PRODUCT FORM HANDLERS ============

    // Variables for product form
    let attrIndex = 1;
    let priceChanging = false;
    let discountChanging = false;
    let salePriceChanging = false;

    // Product type change handler
    const productTypeSelect = document.getElementById('product_type');
    if (productTypeSelect) {
        productTypeSelect.addEventListener('change', function() {
            const variationsSection = document.getElementById('variationsSection');
            variationsSection.style.display = this.value === 'variable' ? 'block' : 'none';
        });
    }

    // Add attribute button
    const addAttributeBtn = document.getElementById('addAttribute');
    if (addAttributeBtn) {
        addAttributeBtn.addEventListener('click', () => {
            const html = `
                <div class="row g-3 align-items-end attribute-row mt-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" placeholder="e.g. Size" name="attributes[${attrIndex}][name]">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="S, M, L" name="attributes[${attrIndex}][values]">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button>
                    </div>
                </div>
            `;
            document.getElementById('attributesContainer').insertAdjacentHTML('beforeend', html);
            attrIndex++;
        });
    }

    // Price calculation handlers
    const priceInput = document.getElementById('price');
    const discountInput = document.getElementById('discount_percent');
    const salePriceInput = document.getElementById('sale_price');

    if (priceInput) {
        priceInput.addEventListener('input', () => {
            if (!priceChanging) {
                priceChanging = true;
                calculateFromPrice();
                priceChanging = false;
            }
        });
    }

    if (discountInput) {
        discountInput.addEventListener('input', () => {
            if (!discountChanging) {
                discountChanging = true;
                calculateFromDiscount();
                discountChanging = false;
            }
        });
    }

    if (salePriceInput) {
        salePriceInput.addEventListener('input', () => {
            if (!salePriceChanging) {
                salePriceChanging = true;
                calculateFromSalePrice();
                salePriceChanging = false;
            }
        });
    }

    function calculateFromPrice() {
        const price = parseFloat(priceInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        if (discount > 0 && discount <= 100) {
            salePriceInput.value = (price * (1 - discount / 100)).toFixed(2);
            document.getElementById('sale_price_note').style.display = 'block';
        }
    }

    function calculateFromDiscount() {
        const price = parseFloat(priceInput.value) || 0;
        const discount = parseFloat(discountInput.value) || 0;
        if (price && discount) {
            salePriceInput.value = (price * (1 - discount / 100)).toFixed(2);
            document.getElementById('sale_price_note').style.display = 'block';
        }
    }

    function calculateFromSalePrice() {
        const price = parseFloat(priceInput.value) || 0;
        const sale = parseFloat(salePriceInput.value) || 0;
        if (price && sale && sale < price) {
            const discount = ((price - sale) / price) * 100;
            discountInput.value = discount.toFixed(2);
            document.getElementById('sale_price_note').style.display = 'block';
        }
    }

    // Apply bulk discount to variations
    const applyBulkDiscountBtn = document.getElementById('applyBulkDiscount');
    if (applyBulkDiscountBtn) {
        applyBulkDiscountBtn.addEventListener('click', () => {
            const bulkDiscount = parseFloat(document.getElementById('bulk_discount').value) || 0;
            if (bulkDiscount < 0 || bulkDiscount > 100) {
                Swal.fire('Warning', 'Please enter a valid discount percentage (0-100).', 'warning');
                return;
            }
            
            document.querySelectorAll('#variationsTable tbody tr').forEach(row => {
                const priceInput = row.querySelector('input[name*="price"]');
                const saleInput = row.querySelector('input[name*="sale_price"]');
                const price = parseFloat(priceInput.value) || 0;
                if (price > 0) {
                    saleInput.value = (price * (1 - bulkDiscount / 100)).toFixed(2);
                }
            });
            
            Swal.fire('Success', `Applied ${bulkDiscount}% discount to all variations.`, 'success');
        });
    }

    // Generate variations button
    const generateVariationsBtn = document.getElementById('generateVariations');
    if (generateVariationsBtn) {
        generateVariationsBtn.addEventListener('click', () => {
            const attrs = [];
            document.querySelectorAll('.attribute-row').forEach(row => {
                const name = row.querySelector('input[name$="[name]"]').value.trim();
                const values = row.querySelector('input[name$="[values]"]').value.split(',').map(v => v.trim()).filter(v => v);
                if (name && values.length) attrs.push({name, values});
            });
            
            if (!attrs.length) {
                document.getElementById('variationsTable').innerHTML = '<p class="text-muted">Please add attributes first</p>';
                return;
            }
            
            // Generate all combinations
            const combos = attrs.reduce((accumulator, currentAttr) => 
                accumulator.flatMap(combo => 
                    currentAttr.values.map(value => ({...combo, [currentAttr.name]: value}))
                ), 
                [{}]
            ).filter(combo => Object.keys(combo).length > 0);
            
            let html = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Variant</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Sale Price</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            combos.forEach((combo, i) => {
                const name = Object.entries(combo).map(([key, value]) => `${key}: ${value}`).join(' | ');
                html += `
                    <tr>
                        <td>
                            <small>${name}</small>
                            ${Object.entries(combo).map(([key, value]) => 
                                `<input type="hidden" name="variations[${i}][attributes][${key}]" value="${value}">`
                            ).join('')}
                        </td>
                        <td>
                            <input type="text" name="variations[${i}][sku]" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="variations[${i}][price]" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="variations[${i}][sale_price]" class="form-control form-control-sm">
                        </td>
                        <td>
                            <input type="number" name="variations[${i}][stock]" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <input type="file" name="variations[${i}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                            <img class="variation-preview mt-2 img-fluid rounded" style="max-height:80px;display:none;">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-variation">Remove</button>
                        </td>
                    </tr>
                `;
            });
            
            html += `</tbody></table>`;
            document.getElementById('variationsTable').innerHTML = html;
        });
    }

    // Thumbnail image preview
    const thumbnailInput = document.getElementById('thumbnail_input');
    if (thumbnailInput) {
        thumbnailInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('thumbnail_preview').src = event.target.result;
                    document.getElementById('thumbnail_preview').style.display = 'block';
                    document.getElementById('thumbnail_placeholder').style.display = 'none';
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }

    // Gallery images preview
    const galleryInput = document.getElementById('gallery_input');
    if (galleryInput) {
        galleryInput.addEventListener('change', function(e) {
            const container = document.getElementById('imageGallery');
            container.innerHTML = '';
            
            Array.from(e.target.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const div = document.createElement('div');
                    div.className = 'col-6 col-md-4 position-relative';
                    div.innerHTML = `
                        <img src="${event.target.result}" class="img-fluid rounded" style="height:100px;object-fit:cover;">
                        <button type="button" class="btn-close position-absolute top-0 end-0 bg-white" onclick="this.parentElement.remove()"></button>
                    `;
                    container.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        });
    }

    // Make gallery sortable
    const imageGallery = document.getElementById('imageGallery');
    if (imageGallery) {
        new Sortable(imageGallery, {
            animation: 150,
            ghostClass: 'bg-light'
        });
    }

    // Variation image preview
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('variation-image') && e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const previewImg = e.target.closest('td').querySelector('.variation-preview');
                if (previewImg) {
                    previewImg.src = event.target.result;
                    previewImg.style.display = 'block';
                }
            };
            reader.readAsDataURL(e.target.files[0]);
        }
    }

    // Remove attribute and variation buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-attribute')) {
            e.target.closest('.attribute-row').remove();
        }
        
        if (e.target.classList.contains('remove-variation')) {
            e.target.closest('tr').remove();
        }
    });

    // Import form submission
    const importForm = document.getElementById('importForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const progress = document.getElementById('importProgress');
            const bar = progress.querySelector('.progress-bar');
            
            progress.style.display = 'block';
            bar.style.width = '0%';
            bar.textContent = '0%';
            
            axios.post('{{ route("products.import") }}', formData, {
                headers: {'Content-Type': 'multipart/form-data'},
                onUploadProgress: function(progressEvent) {
                    const percent = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    bar.style.width = percent + '%';
                    bar.textContent = percent + '%';
                }
            })
            .then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Import completed successfully.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Import error:', error);
                let errorMessage = 'Import failed. Please try again.';
                if (error.response && error.response.data && error.response.data.message) {
                    errorMessage = error.response.data.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
                progress.style.display = 'none';
            });
        });
    }

    // Bulk edit form submission
    const bulkEditForm = document.getElementById('bulkEditForm');
    if (bulkEditForm) {
        bulkEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Applying changes...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            axios.post('{{ route("products.bulkUpdate") }}', new FormData(this))
                .then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Bulk update completed successfully.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                })
                .catch(error => {
                    console.error('Bulk update error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Bulk update failed. Please try again.'
                    });
                });
        });
    }

    // Product form submission
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productId = document.getElementById('product_id').value;
            const url = productId ? `/products/${productId}` : '/products';
            const formData = new FormData(this);
            
            if (productId) {
                formData.append('_method', 'PUT');
            }
            
            const btn = document.getElementById('submitBtn');
            const spinner = document.getElementById('submitSpinner');
            btn.disabled = true;
            spinner.classList.remove('d-none');
            
            axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            .then(response => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.data.message || 'Product saved successfully.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            })
            .catch(error => {
                console.error('Save error:', error);
                let errorMessage = 'Error saving product. Please try again.';
                
                if (error.response && error.response.data) {
                    if (error.response.data.message) {
                        errorMessage = error.response.data.message;
                    } else if (error.response.data.errors) {
                        const errors = Object.values(error.response.data.errors).flat();
                        errorMessage = errors.join('<br>');
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage
                });
            })
            .finally(() => {
                btn.disabled = false;
                spinner.classList.add('d-none');
            });
        });
    }

    // Reset form function
    window.resetForm = function() {
        // Reset form inputs
        const productForm = document.getElementById('productForm');
        if (productForm) {
            productForm.reset();
        }
        
        // Clear hidden fields
        document.getElementById('product_id').value = '';
        
        // Reset modal title and button
        document.getElementById('modalTitle').textContent = 'Add Product';
        document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span> Save Product';
        
        // Hide variations section
        document.getElementById('variationsSection').style.display = 'none';
        
        // Reset attributes container
        const container = document.getElementById('attributesContainer');
        if (container) {
            container.innerHTML = `
                <div class="row g-3 align-items-end attribute-row">
                    <div class="col-md-5">
                        <input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button>
                    </div>
                </div>`;
        }
        
        // Clear variations table
        document.getElementById('variationsTable').innerHTML = '';
        
        // Clear image gallery
        document.getElementById('imageGallery').innerHTML = '';
        
        // Reset thumbnail preview
        const thumbPreview = document.getElementById('thumbnail_preview');
        const thumbPlaceholder = document.getElementById('thumbnail_placeholder');
        if (thumbPreview && thumbPlaceholder) {
            thumbPreview.style.display = 'none';
            thumbPreview.src = '';
            thumbPlaceholder.style.display = 'block';
        }
        
        // Reset sale price note
        const salePriceNote = document.getElementById('sale_price_note');
        if (salePriceNote) {
            salePriceNote.style.display = 'none';
        }
        
        // Reset attribute index
        attrIndex = 1;
        
        // Close modal if open
        const modalElement = document.getElementById('showModal');
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        }
    };
});
</script>
@endsection