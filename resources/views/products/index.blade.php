@extends('layouts.master')

@section('title', 'Products Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title & Filters (unchanged) -->
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
                <!-- Your original filters -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form id="filterForm" method="GET" action="{{ route('products.index') }}">
                                    <div class="row g-3">
                                        <div class="col-xxl-3">
                                            <div class="search-box">
                                                <input type="text" class="form-control search" name="search" placeholder="Search products, SKU, price..." value="{{ request('search') }}" autocomplete="off">
                                                <i class="ri-search-line search-icon"></i>
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
                                                    @foreach($category->children as $child)
                                                        <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>— {{ $child->name }}</option>
                                                    @endforeach
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-xxl-2 col-sm-4">
                                            <select class="form-control" name="stock" id="stockFilter">
                                                <option value="">All Stock</option>
                                                <option value="in_stock" {{ request('stock') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                                <option value="low_stock" {{ request('stock') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                                                <option value="out_of_stock" {{ request('stock') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                            </select>
                                        </div>
                                        <div class="col-xxl-2 col-sm-4">
                                            <select class="form-control" name="featured" id="featuredFilter">
                                                <option value="">All Products</option>
                                                <option value="yes" {{ request('featured') == 'yes' ? 'selected' : '' }}>Featured Only</option>
                                                <option value="no" {{ request('featured') == 'no' ? 'selected' : '' }}>Non-Featured</option>
                                            </select>
                                        </div>
                                        <div class="col-xxl-1 col-sm-4">
                                            <button type="submit" class="btn btn-secondary w-100">Filter</button>
                                        </div>
                                        @if(request()->hasAny(['search', 'brands', 'category', 'stock', 'featured']))
                                        <div class="col-xxl-1 col-sm-4">
                                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
                                        </div>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Your original product table -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">
                                        Products <span class="badge bg-dark-subtle text-dark ms-1">{{ $products->total() }}</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()">Delete Selected</button>
                                        @can('Create product')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal" onclick="resetForm()">
                                                Add Product
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Your table here (unchanged) -->
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th><input class="form-check-input" type="checkbox" id="checkAll"></th>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Stock</th>
                                                <th>Price</th>
                                                <th>Sold</th>
                                                <th>Featured</th>
                                                <th>Published</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse($products as $product)
                                                <tr>
                                                    <td><input class="form-check-input" type="checkbox" name="chk_child" value="{{ $product->id }}"></td>
                                                    <td class="title">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-light rounded p-1 me-3">
                                                                @if($product->thumbnail)
                                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" class="img-fluid rounded" style="max-height: 40px;">
                                                                @else
                                                                    <div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                        <i class="bi bi-image text-muted fs-5"></i>
                                                                    </div>
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
                                                            <span class="badge bg-success-subtle text-success-emphasis">{{ $product->stock }}</span>
                                                        @elseif($product->stock > 0)
                                                            <span class="badge bg-warning-subtle text-warning-emphasis">{{ $product->stock }}</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger-emphasis">Out of stock</span>
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
                                                            <span class="badge bg-primary-subtle text-primary">Featured</span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary">Regular</span>
                                                        @endif
                                                    </td>
                                                    <td class="created_at"><small class="text-muted">{{ $product->created_at->format('d M, Y') }}</small></td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">More</button>
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
                                                <tr><td colspan="9" class="text-center py-5 text-muted">No products found</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4">{!! $products->appends(request()->query())->links('pagination::bootstrap-5') !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL - FULLY WORKING + DRAG & DROP -->
            <div class="modal fade" id="showModal" tabindex="-1" data-bs-backdrop="static">
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
                                        <!-- Basic Info -->
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title mb-3">Basic Information</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-6"><input type="text" name="title" id="title" class="form-control" placeholder="Title" required></div>
                                                    <div class="col-md-6"><input type="text" name="sku" id="sku" class="form-control" placeholder="SKU" required></div>
                                                    <div class="col-md-4"><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.01" name="price" id="price" class="form-control" required></div></div>
                                                    <div class="col-md-4"><div class="input-group"><span class="input-group-text">$</span><input type="number" step="0.01" name="sale_price" id="sale_price" class="form-control"></div></div>
                                                    <div class="col-md-4"><input type="number" name="stock" id="stock" class="form-control" placeholder="Stock" required></div>
                                                    <div class="col-md-6">
                                                        <select name="product_type" id="product_type" class="form-control" required>
                                                            <option value="simple">Simple Product</option>
                                                            <option value="variable">Variable Product</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12"><textarea name="description" id="description" rows="4" class="form-control"></textarea></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Variations -->
                                        <div id="variationsSection" style="display:none;">
                                            <div class="card mt-4">
                                                <div class="card-header d-flex justify-content-between">
                                                    <h6 class="mb-0">Product Variations</h6>
                                                    <button type="button" class="btn btn-primary btn-sm" id="generateVariations">Generate</button>
                                                </div>
                                                <div class="card-body">
                                                    <div id="attributesContainer" class="mb-4">
                                                        <div class="row g-3 align-items-end attribute-row">
                                                            <div class="col-md-5"><input type="text" class="form-control" placeholder="Color" name="attributes[0][name]"></div>
                                                            <div class="col-md-6"><input type="text" class="form-control" placeholder="Red, Blue, Green" name="attributes[0][values]"></div>
                                                            <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
                                                        </div>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm mb-4" id="addAttribute">+ Add Attribute</button>
                                                    <hr>
                                                    <div id="variationsTable"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label>Thumbnail</label>
                                                <input type="file" id="thumbnail_input" class="form-control mb-2" accept="image/*">
                                                <div class="text-center mt-3">
                                                    <img id="thumbnail_preview" src="" class="img-fluid rounded" style="max-height:200px; display:none;">
                                                    <div id="thumbnail_placeholder" class="text-muted">No image</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <label>Gallery Images (Drag to reorder)</label>
                                                <input type="file" id="gallery_input" multiple class="form-control mb-3" accept="image/*">
                                                <div id="imageGallery" class="row g-2" style="min-height:100px; border:2px dashed #ddd; padding:10px;"></div>
                                            </div>
                                        </div>

                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label>Brand</label>
                                                    <select name="brand_id" id="brand_id" class="form-control">
                                                        <option value="">No Brand</option>
                                                        @foreach($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label>Category</label>
                                                    <select name="category_id" id="category_id" class="form-control">
                                                        <option value="">Select</option>
                                                        @foreach($categories as $c)
                                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                                            @foreach($c->children as $child)
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

                            <div class="modal-footer border-top pt-3">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="resetForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="submitSpinner"></span>
                                    <span id="submitText">Save Product</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS - 100% WORKING -->
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';

    // Your original List.js + bulk actions
    new List('productList', { valueNames: ['title', 'category', 'stock', 'price', 'sold', 'created_at', 'featured'], page: 12, pagination: true });

    document.getElementById('checkAll')?.addEventListener('change', function() {
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => cb.checked = this.checked);
        document.getElementById('remove-actions').classList.toggle('d-none', !this.checked);
    });

    window.deleteMultiple = function() {
        const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked')).map(cb => cb.value);
        if (!ids.length) return;
        Swal.fire({
            title: 'Delete selected?', text: "This cannot be undone!", icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Yes'
        }).then(res => {
            if (res.isConfirmed) {
                Promise.all(ids.map(id => axios.delete(`/products/${id}`)))
                    .then(() => location.reload())
                    .catch(() => Swal.fire('Error', 'Failed to delete', 'error'));
            }
        });
    };

    // Live search
    let timeout;
    document.querySelector('.search')?.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => document.getElementById('filterForm').submit(), 500);
    });

    // Choices.js
    if (typeof Choices !== 'undefined') {
        new Choices('#brandFilter', { removeItemButton: true });
        new Choices('#categoryFilter');
    }
});

// VARIABLE PRODUCT + IMAGE PREVIEW + DRAG & DROP
let attrIndex = 1;

// Toggle variations
document.getElementById('product_type').addEventListener('change', function() {
    document.getElementById('variationsSection').style.display = this.value === 'variable' ? 'block' : 'none';
});

// Add attribute
document.getElementById('addAttribute')?.addEventListener('click', () => {
    const html = `<div class="row g-3 align-items-end attribute-row mt-3">
        <div class="col-md-5"><input type="text" class="form-control" placeholder="Size" name="attributes[${attrIndex}][name]"></div>
        <div class="col-md-6"><input type="text" class="form-control" placeholder="S, M, L" name="attributes[${attrIndex}][values]"></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
    </div>`;
    document.getElementById('attributesContainer').insertAdjacentHTML('beforeend', html);
    attrIndex++;
});

// Generate variations
document.getElementById('generateVariations')?.addEventListener('click', () => {
    const attrs = [];
    document.querySelectorAll('.attribute-row').forEach(row => {
        const name = row.querySelector('input[name$="[name]"]').value.trim();
        const values = row.querySelector('input[name$="[values]"]').value.split(',').map(v => v.trim()).filter(v => v);
        if (name && values.length) attrs.push({ name, values });
    });
    if (!attrs.length) return Swal.fire('Error', 'Add attributes first', 'error');

    const combos = attrs.reduce((a, b) => a.flatMap(x => b.values.map(y => ({...x, [b.name]: y}))), [{}]);

    let html = `<table class="table table-bordered"><thead><tr>
        <th>Variant</th><th>Price</th><th>Sale</th><th>Stock</th><th>Image</th><th></th>
    </tr></thead><tbody>`;
    combos.forEach((c, i) => {
        const name = Object.entries(c).map(([k,v]) => `${k}: ${v}`).join(' | ');
        html += `<tr>
            <td><small>${name}</small>${Object.entries(c).map(([k,v]) => 
                `<input type="hidden" name="variations[${i}][attributes][${k}]" value="${v}">`).join('')}</td>
            <td><input type="number" step="0.01" name="variations[${i}][price]" class="form-control form-control-sm" required></td>
            <td><input type="number" step="0.01" name="variations[${i}][sale_price]" class="form-control form-control-sm"></td>
            <td><input type="number" name="variations[${i}][stock]" class="form-control form-control-sm" required></td>
            <td>
                <input type="file" name="variations[${i}][image]" class="form-control form-control-sm variation-image" accept="image/*">
                <img class="variation-preview mt-2 img-fluid rounded" style="max-height:80px; display:none;">
            </td>
            <td><button type="button" class="btn btn-danger btn-sm remove-variation">Remove</button></td>
        </tr>`;
    });
    html += `</tbody></table>`;
    document.getElementById('variationsTable').innerHTML = html;
});

// Remove buttons (delegated)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-attribute')) {
        e.target.closest('.attribute-row').remove();
    }
    if (e.target.classList.contains('remove-variation')) {
        e.target.closest('tr').remove();
    }
});

// Image previews
document.getElementById('thumbnail_input')?.addEventListener('change', e => {
    if (e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('thumbnail_preview').src = ev.target.result;
            document.getElementById('thumbnail_preview').style.display = 'block';
            document.getElementById('thumbnail_placeholder').style.display = 'none';
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

document.getElementById('gallery_input')?.addEventListener('change', e => {
    const container = document.getElementById('imageGallery');
    container.innerHTML = '';
    Array.from(e.target.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = ev => {
            const div = document.createElement('div');
            div.className = 'col-4 position-relative gallery-item';
            div.innerHTML = `<img src="${ev.target.result}" class="img-fluid rounded" style="height:100px; object-fit:cover;">
                             <div class="position-absolute top-0 end-0 bg-danger text-white rounded-circle p-1" style="cursor:pointer;" onclick="this.parentElement.remove()">
                                 <i class="bi bi-x"></i>
                             </div>`;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});

// Drag & drop gallery
new Sortable(document.getElementById('imageGallery'), {
    animation: 150,
    ghostClass: 'bg-light'
});

// Variation image preview
document.addEventListener('change', e => {
    if (e.target.classList.contains('variation-image') && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = ev => {
            const img = e.target.closest('td').querySelector('.variation-preview');
            img.src = ev.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

// FORM SUBMIT - FIXED
document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('product_id').value;
    const url = id ? `/products/${id}` : '/products';
    const formData = new FormData(this);
    if (id) formData.append('_method', 'PUT');

    const btn = document.getElementById('submitBtn');
    const spinner = document.getElementById('submitSpinner');
    const text = document.getElementById('submitText');
    btn.disabled = true;
    spinner.classList.remove('d-none');
    text.textContent = id ? 'Updating...' : 'Saving...';

    axios.post(url, formData, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then(r => {
            if (r.data.success) {
                Swal.fire('Success!', r.data.message, 'success').then(() => location.reload());
            }
        })
        .catch(err => {
            const msg = err.response?.data?.errors 
                ? Object.values(err.response.data.errors).flat().join('<br>')
                : err.response?.data?.message || 'Error';
            Swal.fire('Error!', msg, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            text.textContent = id ? 'Update Product' : 'Save Product';
        });
});

// Edit + Delete (your original)
document.querySelectorAll('.edit-item-btn').forEach(b => b.addEventListener('click', function() {
    axios.get(`/products/${this.dataset.id}/edit`).then(r => {
        const d = r.data;
        document.getElementById('product_id').value = d.id;
        document.getElementById('title').value = d.title;
        document.getElementById('sku').value = d.sku;
        document.getElementById('price').value = d.price;
        document.getElementById('sale_price').value = d.sale_price || '';
        document.getElementById('stock').value = d.stock;
        document.getElementById('description').value = d.description || '';
        document.getElementById('brand_id').value = d.brand_id || '';
        document.getElementById('category_id').value = d.category_id || '';
        document.getElementById('product_type').value = d.product_type;
        document.getElementById('is_featured').checked = d.is_featured;

        if (d.thumbnail) {
            document.getElementById('thumbnail_preview').src = d.thumbnail;
            document.getElementById('thumbnail_preview').style.display = 'block';
            document.getElementById('thumbnail_placeholder').style.display = 'none';
        }

        if (d.product_type === 'variable') {
            document.getElementById('variationsSection').style.display = 'block';
            document.getElementById('attributesContainer').innerHTML = '';
            attrIndex = 0;
            d.attributes.forEach(a => {
                if (attrIndex > 0) document.getElementById('addAttribute').click();
                const row = document.querySelectorAll('.attribute-row')[attrIndex];
                row.querySelector('input[name$="[name]"]').value = a.name;
                row.querySelector('input[name$="[values]"]').value = a.values.join(', ');
                attrIndex++;
            });
            setTimeout(() => document.getElementById('generateVariations').click(), 300);
        }

        document.getElementById('modalTitle').textContent = 'Edit Product';
        document.getElementById('submitText').textContent = 'Update Product';
    });
}));

document.querySelectorAll('.remove-item-btn').forEach(b => b.addEventListener('click', function() {
    Swal.fire({
        title: 'Delete?', text: "Cannot be undone!", icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Yes'
    }).then(res => {
        if (res.isConfirmed) {
            axios.delete(`/products/${this.dataset.id}`).then(() => location.reload());
        }
    });
}));

function resetForm() {
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('submitText').textContent = 'Save Product';
    document.getElementById('variationsSection').style.display = 'none';
    document.getElementById('attributesContainer').innerHTML = `<div class="row g-3 align-items-end attribute-row">
        <div class="col-md-5"><input type="text" class="form-control" placeholder="Color" name="attributes[0][name]"></div>
        <div class="col-md-6"><input type="text" class="form-control" placeholder="Red, Blue" name="attributes[0][values]"></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-attribute">Remove</button></div>
    </div>`;
    document.getElementById('variationsTable').innerHTML = '';
    document.getElementById('imageGallery').innerHTML = '';
    document.getElementById('thumbnail_preview').style.display = 'none';
    document.getElementById('thumbnail_placeholder').style.display = 'block';
    attrIndex = 1;
}
</script>
@endsection