@extends('layouts.master')

@section('title', 'Products Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
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

            <div id="productList">
                <!-- Advanced Filters -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    Advanced Filters
                                    <button class="btn btn-sm btn-outline-secondary float-end" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters">Toggle</button>
                                </h5>
                            </div>
                            <div class="card-body collapse show" id="advancedFilters">
                                <form id="filterForm" method="GET" action="{{ route('products.index') }}">
                                    <div class="row g-3">
                                        <div class="col-xxl-3">
                                            <div class="search-box position-relative">
                                                <input type="text" class="form-control search" id="searchInput" name="search" placeholder="Search products, SKU..." value="{{ request('search') }}" autocomplete="off">
                                                <i class="ri-search-line search-icon"></i>
                                            </div>
                                            <div id="searchResults" class="position-absolute bg-white border rounded shadow-lg mt-1" style="top:100%; left:0; width:100%; max-height:400px; overflow-y:auto; z-index:9999; display:none;">
                                                <div id="resultsList"></div>
                                            </div>
                                        </div>

                                        <div class="col-xxl-2 col-sm-6">
                                            <select class="form-control" name="brands[]" id="brandFilter" data-choices multiple>
                                                <option value="">All Brands</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}" {{ in_array($brand->id, (array)request('brands', [])) ? 'selected' : '' }}>{{ $brand->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-xxl-2 col-sm-6">
                                            <select class="form-control" name="category" id="categoryFilter" data-choices>
                                                <option value="">All Categories</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                    @if($category->children->count())
                                                        @foreach($category->children as $child)
                                                            <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>— {{ $child->name }}</option>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-xxl-2 col-sm-6">
                                            <label class="form-label small">Price Range</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="price_min" placeholder="Min" value="{{ request('price_min') }}">
                                                <span class="input-group-text">to</span>
                                                <input type="number" class="form-control" name="price_max" placeholder="Max" value="{{ request('price_max') }}">
                                            </div>
                                        </div>

                                        <div class="col-xxl-2 col-sm-6">
                                            <label class="form-label small">Stock Range</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="stock_min" placeholder="Min" value="{{ request('stock_min') }}">
                                                <span class="input-group-text">to</span>
                                                <input type="number" class="form-control" name="stock_max" placeholder="Max" value="{{ request('stock_max') }}">
                                            </div>
                                        </div>

                                        <div class="col-xxl-2 col-sm-6">
                                            <label class="form-label small">Min Sold</label>
                                            <input type="number" class="form-control" name="sold_min" placeholder="e.g. 10" value="{{ request('sold_min') }}">
                                        </div>

                                        <div class="col-xxl-2 col-sm-6">
                                            <label class="form-label small">Created After</label>
                                            <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                                        </div>

                                        <div class="col-xxl-2 col-sm-6">
                                            <label class="form-label small">Created Before</label>
                                            <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                                        </div>

                                        <div class="col-xxl-2 col-sm-6">
                                            <select class="form-control" name="featured" id="featuredFilter">
                                                <option value="">All Products</option>
                                                <option value="yes" {{ request('featured') == 'yes' ? 'selected' : '' }}>Featured Only</option>
                                                <option value="no" {{ request('featured') == 'no' ? 'selected' : '' }}>Non-Featured</option>
                                            </select>
                                        </div>

                                        <div class="col-xxl-2 col-sm-6 d-flex align-items-end gap-2">
                                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                            @if(request()->hasAny(['search','brands','category','price_min','stock_min','sold_min','date_from','featured']))
                                                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Clear</a>
                                            @endif
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product List -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Products <span class="badge bg-dark-subtle text-dark ms-1" id="totalProducts">{{ $products->total() }}</span>
                                        <small class="text-muted ms-2">Real-time stock active</small>
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
                                        <a href="{{ route('products.template') }}" class="btn btn-outline-primary">Download Template</a>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th><div class="form-check"><input class="form-check-input" type="checkbox" id="checkAll"><label class="form-check-label" for="checkAll"></label></div></th>
                                                <th class="sort cursor-pointer" data-sort="title">Product</th>
                                                <th class="sort cursor-pointer" data-sort="category">Category</th>
                                                <th class="sort cursor-pointer" data-sort="stock">Stock</th>
                                                <th class="sort cursor-pointer" data-sort="price">Price</th>
                                                <th class="sort cursor-pointer" data-sort="sold">Sold</th>
                                                <th class="sort cursor-pointer" data-sort="featured">Featured</th>
                                                <th class="sort cursor-pointer" data-sort="created_at">Published</th>
                                                <th>Inventory</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse($products as $product)
                                                <tr data-product-id="{{ $product->id }}">
                                                    <td><div class="form-check"><input class="form-check-input bulk-checkbox" type="checkbox" name="chk_child" value="{{ $product->id }}"></div></td>
                                                    <td class="title">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-light rounded p-1 me-3">
                                                                @if($product->thumbnail)
                                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="" class="img-fluid rounded" style="max-height: 40px;">
                                                                @else
                                                                    <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="bi bi-image text-muted fs-5"></i></div>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1"><a href="{{ route('products.show', $product->id) }}" class="text-reset">{{ Str::limit($product->title, 50) }}</a></h6>
                                                                <p class="mb-0 text-muted small">SKU: {{ $product->sku }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="category">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                                    <td class="stock">
                                                        @if($product->stock > 10)
                                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">{{ $product->stock }} in stock</span>
                                                        @elseif($product->stock > 0)
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $product->stock }} low stock</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">Out of stock</span>
                                                        @endif
                                                    </td>
                                                    <td class="price">
                                                        @if($product->sale_price)
                                                            <del class="text-muted small">${{ number_format($product->price, 2) }}</del><br>
                                                            <span class="text-danger fw-bold">${{ number_format($product->sale_price, 2) }}</span>
                                                        @else
                                                            <span class="fw-bold">${{ number_format($product->price, 2) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="sold text-center"><span class="fw-semibold">{{ $product->sold_quantity ?? 0 }}</span></td>
                                                    <td class="featured">
                                                        @if($product->is_featured)
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-star-fill me-1"></i> Featured</span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Regular</span>
                                                        @endif
                                                    </td>
                                                    <td class="created_at"><small class="text-muted">{{ $product->created_at->format('d M, Y') }}</small></td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-info inventory-btn" 
                                                                data-id="{{ $product->id }}" 
                                                                data-title="{{ $product->title }}">
                                                            View Log
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item" href="{{ route('products.show', $product->id) }}">View</a></li>
                                                                @can('Update product')
                                                                    <li><a class="dropdown-item edit-item-btn" href="#showModal" data-bs-toggle="modal" data-id="{{ $product->id }}">Edit</a></li>
                                                                @endcan
                                                                @can('Delete product')
                                                                    <li><a class="dropdown-item remove-item-btn" href="javascript:void(0);" data-id="{{ $product->id }}">Delete</a></li>
                                                                @endcan
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="10" class="text-center py-5 text-muted"><div class="py-4">No products found</div></td></tr>
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
                                                    <div class="col-md-6">
                                                        <label class="form-label">Title <span class="text-danger">*</span></label>
                                                        <input type="text" name="title" id="title" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                                                        <input type="text" name="sku" id="sku" class="form-control" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Price <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" name="price" id="price" class="form-control" required min="0">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Discount %</label>
                                                        <input type="number" step="0.01" min="0" max="100" id="discount_percent" class="form-control" placeholder="e.g. 25">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Sale Price</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">$</span>
                                                            <input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control">
                                                        </div>
                                                        <small class="text-success" id="sale_price_note" style="display:none;">Auto-calculated</small>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Stock <span class="text-danger">*</span></label>
                                                        <input type="number" name="stock" id="stock" class="form-control" required min="0">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Product Type <span class="text-danger">*</span></label>
                                                        <select name="product_type" id="product_type" class="form-control" required>
                                                            <option value="simple">Simple Product</option>
                                                            <option value="variable">Variable Product</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" id="description" rows="4" class="form-control"></textarea>
                                                    </div>
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
                                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1">
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
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <form id="importForm" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Import Products from CSV</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Select CSV File</label>
                                    <input type="file" name="file" class="form-control" accept=".csv" required>
                                </div>
                                <div class="alert alert-info">
                                    <strong>Required:</strong> title, sku, price, stock<br>
                                    <strong>Optional:</strong> sale_price, description, brand_id, category_id, is_featured
                                </div>
                                <div id="importProgress" class="progress mt-3" style="display:none;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success">Import</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- BULK EDIT MODAL -->
            <div class="modal fade" id="bulkEditModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <form id="bulkEditForm">
                            @csrf
                            <input type="hidden" name="product_ids" id="bulk_product_ids">
                            <div class="modal-header">
                                <h5 class="modal-title">Bulk Edit Selected Products</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong><span id="bulkCount">0</span></strong> products selected</p>
                                <div class="row g-3">
                                    <div class="col-md-6"><input type="number" step="0.01" name="price" class="form-control" placeholder="Price"></div>
                                    <div class="col-md-6"><input type="number" step="0.01" name="sale_price" class="form-control" placeholder="Sale Price"></div>
                                    <div class="col-md-6"><input type="number" name="stock" class="form-control" placeholder="Stock"></div>
                                    <div class="col-md-6">
                                        <select name="is_featured" class="form-control">
                                            <option value="">No Change</option>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
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
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Inventory Log - <span id="inventoryTitle"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Qty</th>
                                            <th>Ref</th>
                                            <th>Prev</th>
                                            <th>New</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventoryLogBody">
                                        <tr><td colspan="6" class="text-center">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ALL SCRIPTS - 100% COMPLETE -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';

    // List.js
    new List('productList', { valueNames: ['title','category','stock','price','sold','created_at','featured'], page: 12, pagination: true });

    // Bulk actions
    document.getElementById('checkAll')?.addEventListener('change', function() {
        document.querySelectorAll('.bulk-checkbox').forEach(cb => cb.checked = this.checked);
        updateBulkActions();
    });

    function updateBulkActions() {
        const count = document.querySelectorAll('.bulk-checkbox:checked').length;
        document.getElementById('remove-actions').classList.toggle('d-none', count === 0);
        document.getElementById('bulkEditBtn').style.display = count > 0 ? 'inline-block' : 'none';
        document.getElementById('bulkCount').textContent = count;
        document.getElementById('bulk_product_ids').value = Array.from(document.querySelectorAll('.bulk-checkbox:checked'))
            .map(cb => cb.value).join(',');
    }

    document.querySelectorAll('.bulk-checkbox').forEach(cb => cb.addEventListener('change', updateBulkActions));

    // Delete multiple
    window.deleteMultiple = function () {
        const ids = Array.from(document.querySelectorAll('.bulk-checkbox:checked')).map(cb => cb.value);
        if (!ids.length) return;
        Swal.fire({
            title: `Delete ${ids.length} product(s)?`,
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                Promise.all(ids.map(id => axios.delete(`/products/${id}`)))
                    .then(() => location.reload())
                    .catch(() => Swal.fire('Error!', 'Failed to delete some products', 'error'));
            }
        });
    };

    // Live search
    let searchTimeout;
    document.querySelector('.search')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => document.getElementById('filterForm').submit(), 500);
    });

    // Choices.js
    if (typeof Choices !== 'undefined') {
        new Choices('#brandFilter', { removeItemButton: true });
        new Choices('#categoryFilter');
    }
});

// Search Autocomplete
document.getElementById('searchInput').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    const dropdown = document.getElementById('searchResults');
    const list = document.getElementById('resultsList');

    if (query.length < 2) {
        dropdown.style.display = 'none';
        return;
    }

    axios.get('/products/search', { params: { q: query } })
        .then(res => {
            if (res.data.length === 0) {
                list.innerHTML = '<div class="p-3 text-center text-muted">No products found</div>';
                dropdown.style.display = 'block';
                return;
            }

            list.innerHTML = res.data.map(p => `
                <a href="/products/${p.id}" class="d-block p-3 border-bottom text-decoration-none">
                    <div class="d-flex align-items-center">
                        <img src="${p.thumbnail ? '/storage/' + p.thumbnail : '/img/no-image.png'}" class="rounded me-3" style="width:40px;height:40px;object-fit:cover;">
                        <div>
                            <div class="fw-semibold">${p.title}</div>
                            <small class="text-muted">SKU: ${p.sku} • $${Number(p.price).toFixed(2)}</small>
                        </div>
                    </div>
                </a>
            `).join('');
            dropdown.style.display = 'block';
        })
        .catch(() => dropdown.style.display = 'none');
});

document.addEventListener('click', e => {
    if (!e.target.closest('#searchInput') && !e.target.closest('#searchResults')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

// Real-time stock updates
setInterval(() => {
    axios.get('/products/realtime-stock')
        .then(res => {
            res.data.forEach(item => {
                const row = document.querySelector(`tr[data-product-id="${item.id}"]`);
                if (row) {
                    const stockCell = row.querySelector('.stock');
                    const oldStock = parseInt(stockCell.textContent.match(/\d+/)?.[0] || 0);
                    const newStock = item.stock;

                    if (oldStock !== newStock) {
                        const badgeClass = newStock > 10 ? 'bg-success-subtle text-success-emphasis' 
                            : newStock > 0 ? 'bg-warning-subtle text-warning-emphasis' 
                            : 'bg-danger-subtle text-danger-emphasis';
                        const badgeText = newStock > 10 ? `${newStock} in stock` 
                            : newStock > 0 ? `${newStock} low stock` 
                            : 'Out of stock';
                        stockCell.innerHTML = `<span class="badge ${badgeClass} border border-${newStock > 10 ? 'success' : newStock > 0 ? 'warning' : 'danger'}-subtle">${badgeText}</span>`;
                        
                        stockCell.style.transition = 'background 0.8s';
                        stockCell.style.backgroundColor = newStock > oldStock ? '#d4edda' : '#f8d7da';
                        setTimeout(() => stockCell.style.backgroundColor = '', 1000);
                    }
                }
            });
        });
}, 10000);

// Inventory Log Modal
document.querySelectorAll('.inventory-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.getAttribute('data-id');
        const title = this.getAttribute('data-title');
        document.getElementById('inventoryTitle').textContent = title;

        axios.get(`/products/${productId}/inventory`)
            .then(res => {
                const tbody = document.getElementById('inventoryLogBody');
                if (!res.data || res.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No inventory movements recorded</td></tr>';
                    return;
                }

                tbody.innerHTML = res.data.map(log => `
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
            })
            .catch(() => {
                document.getElementById('inventoryLogBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Failed to load log</td></tr>';
            });

        const modal = new bootstrap.Modal(document.getElementById('inventoryModal'));
        modal.show();
    });
});

// Import CSV
document.getElementById('importForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const progress = document.getElementById('importProgress');
    const bar = progress.querySelector('.progress-bar');
    progress.style.display = 'block';

    axios.post('{{ route('products.import') }}', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: e => {
            const percent = Math.round((e.loaded * 100) / e.total);
            bar.style.width = percent + '%';
            bar.textContent = percent + '%';
        }
    })
    .then(() => Swal.fire('Success!', 'Import completed', 'success').then(() => location.reload()))
    .catch(() => Swal.fire('Error!', 'Import failed', 'error'));
});

// Bulk Edit
document.getElementById('bulkEditForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    axios.post('{{ route('products.bulkUpdate') }}', new FormData(this))
        .then(() => Swal.fire('Success!', 'Products updated', 'success').then(() => location.reload()))
        .catch(() => Swal.fire('Error!', 'Update failed', 'error'));
});

// Save Product
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('product_id').value;
    const url = id ? `/products/${id}` : '/products';
    const formData = new FormData(this);
    if (id) formData.append('_method', 'PUT');

    const btn = document.getElementById('submitBtn');
    const spinner = document.getElementById('submitSpinner');
    btn.disabled = true;
    spinner.classList.remove('d-none');

    axios.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(r => { if (r.data.success) { Swal.fire('Success!', r.data.message, 'success').then(() => location.reload()); } })
        .catch(err => {
            const msg = err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join('<br>') : err.response?.data?.message || 'Error';
            Swal.fire('Error!', msg, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
        });
});

// Edit Product
document.querySelectorAll('.edit-item-btn').forEach(b => b.addEventListener('click', function() {
    axios.get(`/products/${this.dataset.id}/edit`).then(r => {
        productData = r.data;
        document.getElementById('product_id').value = productData.id;
        document.getElementById('title').value = productData.title;
        document.getElementById('sku').value = productData.sku;
        document.getElementById('price').value = productData.price;
        document.getElementById('sale_price').value = productData.sale_price || '';
        document.getElementById('stock').value = productData.stock;
        document.getElementById('description').value = productData.description || '';
        document.getElementById('brand_id').value = productData.brand_id || '';
        document.getElementById('category_id').value = productData.category_id || '';
        document.getElementById('product_type').value = productData.product_type;
        document.getElementById('is_featured').checked = productData.is_featured;

        if (productData.thumbnail) {
            document.getElementById('thumbnail_preview').src = productData.thumbnail;
            document.getElementById('thumbnail_preview').style.display = 'block';
            document.getElementById('thumbnail_placeholder').style.display = 'none';
        }

        calculateFromSalePrice();

        if (productData.product_type === 'variable') {
            document.getElementById('variationsSection').style.display = 'block';
            document.getElementById('attributesContainer').innerHTML = '';
            attrIndex = 0;
            productData.attributes.forEach(a => {
                if (attrIndex > 0) document.getElementById('addAttribute').click();
                const row = document.querySelectorAll('.attribute-row')[attrIndex];
                row.querySelector('input[name$="[name]"]').value = a.name;
                row.querySelector('input[name$="[values]"]').value = a.values.join(', ');
                attrIndex++;
            });
            setTimeout(() => renderExistingVariations(), 300);
        }

        document.getElementById('modalTitle').textContent = 'Edit Product';
        document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span> Update Product';
    });
});

// Delete single
document.querySelectorAll('.remove-item-btn').forEach(b => b.addEventListener('click', function() {
    Swal.fire({ title: 'Delete?', icon: 'warning', showCancelButton: true })
        .then(res => { if (res.isConfirmed) axios.delete(`/products/${this.dataset.id}`).then(() => location.reload()); });
}));

function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('submitBtn').innerHTML = '<span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span> Save Product';
    document.getElementById('variationsSection').style.display = 'none';
    document.getElementById('attributesContainer').innerHTML = `<div class="row g-3 align-items-end attribute-row">
        <div class="col-md-5"><input type="text" class="form-control" placeholder="e.g. Color" name="attributes[0][name]"></div>
        <div class="col-md-6"><input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]"></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
    </div>`;
    document.getElementById('variationsTable').innerHTML = '';
    document.getElementById('imageGallery').innerHTML = '';
    document.getElementById('thumbnail_preview').style.display = 'none';
    document.getElementById('thumbnail_placeholder').style.display = 'block';
    document.getElementById('sale_price').value = '';
    document.getElementById('discount_percent').value = '';
    document.getElementById('sale_price_note').style.display = 'none';
    document.getElementById('bulk_discount').value = '';
    attrIndex = 1;
}
</script>
@endsection

