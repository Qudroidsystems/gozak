{{-- resources/views/products/index.blade.php --}}
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
                <!-- Filters -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search products, SKU, price...">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" name="brand_filter" data-choices data-choices-search-false multiple>
                                            <option value="">All Brands</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <select class="form-control" name="category_filter" data-choices data-choices-search-false>
                                            <option value="">All Categories</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                @if($category->children->count())
                                                    @foreach($category->children as $child)
                                                        <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-xxl-2 col-sm-6">
                                        <select class="form-control" name="stock_status">
                                            <option value="">All Stock</option>
                                            <option value="in_stock">In Stock</option>
                                            <option value="low_stock">Low Stock (<10)</option>
                                            <option value="out_of_stock">Out of Stock</option>
                                        </select>
                                    </div>
                                    <div class="col-xxl-1 col-sm-6">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData()">
                                            <i class="bi bi-funnel align-baseline me-1"></i> Filter
                                        </button>
                                    </div>
                                </div>
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
                                        Products <span class="badge bg-dark-subtle text-dark ms-1">{{ $products->total() }}</span>
                                    </h5>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="d-flex flex-wrap align-items-start gap-2">
                                        <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                            <i class="ri-delete-bin-2-line"></i>
                                        </button>
                                        @can('Create product')
                                            <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                                                <i class="bi bi-plus-circle align-baseline me-1"></i> Add Product
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0">
                                        <thead class="table-active">
                                            <tr>
                                                <th>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkAll">
                                                        <label class="form-check-label" for="checkAll"></label>
                                                    </div>
                                                </th>
                                                <th class="sort cursor-pointer" data-sort="title">Product</th>
                                                <th class="sort cursor-pointer" data-sort="category">Category</th>
                                                <th class="sort cursor-pointer" data-sort="stock">Stock</th>
                                                <th class="sort cursor-pointer" data-sort="price">Price</th>
                                                <th class="sort cursor-pointer" data-sort="sold">Sold</th>
                                                <th class="sort cursor-pointer" data-sort="featured">Featured</th>
                                                <th class="sort cursor-pointer" data-sort="created_at">Published</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse($products as $product)
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $product->id }}">
                                                        </div>
                                                    </td>
                                                    <td class="title">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-light rounded p-1 me-3">
                                                                @if($product->thumbnail)
                                                                    <img src="{{ asset('storage/' . $product->thumbnail) }}" alt="" class="img-fluid">
                                                                @else
                                                                    <i class="bi bi-image text-muted fs-3"></i>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1">
                                                                    <a href="{{ route('products.show', $product->id) }}" class="text-reset">
                                                                        {{ Str::limit($product->title, 50) }}
                                                                    </a>
                                                                </h6>
                                                                <p class="mb-0 text-muted small">SKU: {{ $product->sku }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="category">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                                    <td class="stock">
                                                        <span class="badge {{ $product->stock > 10 ? 'bg-success' : ($product->stock > 0 ? 'bg-warning' : 'bg-danger') }}-subtle">
                                                            {{ $product->stock }}
                                                        </span>
                                                    </td>
                                                    <td class="price">
                                                        @if($product->sale_price)
                                                            <del class="text-muted">${{ number_format($product->price, 2) }}</del>
                                                            <span class="text-danger fw-bold">${{ number_format($product->sale_price, 2) }}</span>
                                                        @else
                                                            <span class="fw-bold">${{ number_format($product->price, 2) }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="sold text-center">{{ $product->sold_quantity ?? 0 }}</td>
                                                    <td class="featured">
                                                        @if($product->is_featured)
                                                            <i class="bi bi-star-fill text-warning"></i>
                                                        @else
                                                            <i class="bi bi-star text-muted"></i>
                                                        @endif
                                                    </td>
                                                    <td class="created_at">{{ $product->created_at->format('d M, Y') }}</td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">
                                                                <i class="bi bi-three-dots-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li><a class="dropdown-item" href="{{ route('products.show', $product->id) }}"><i class="ph-eye me-1"></i> View</a></li>
                                                                @can('Update product')
                                                                    <li><a class="dropdown-item edit-item-btn" href="#showModal" data-bs-toggle="modal" data-id="{{ $product->id }}"><i class="ph-pencil me-1"></i> Edit</a></li>
                                                                @endcan
                                                                @can('Delete product')
                                                                    <li><a class="dropdown-item remove-item-btn" href="javascript:void(0);" data-id="{{ $product->id }}"><i class="ph-trash me-1"></i> Delete</a></li>
                                                                @endcan
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center py-5 text-muted">No products found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="noresult" style="display: none">
                                    <div class="text-center py-4">
                                        <i class="bi bi-search display-4 text-primary"></i>
                                        <h5 class="mt-2">Sorry! No Result Found</h5>
                                    </div>
                                </div>

                                <!-- Pagination -->
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

            <!-- Add/Edit Modal -->
            <div class="modal fade" id="showModal" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content">
                        <form id="productForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" id="product_id">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalTitle">Add Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-4">
                                    <div class="col-lg-8">
                                        <div class="row g-3">
                                            <div class="col-md-6"><label>Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" required></div>
                                            <div class="col-md-6"><label>SKU <span class="text-danger">*</span></label><input type="text" name="sku" class="form-control" required></div>
                                            <div class="col-md-4"><label>Price <span class="text-danger">*</span></label><input type="number" step="0.01" name="price" class="form-control" required></div>
                                            <div class="col-md-4"><label>Sale Price</label><input type="number" step="0.01" name="sale_price" class="form-control"></div>
                                            <div class="col-md-4"><label>Stock <span class="text-danger">*</span></label><input type="number" name="stock" class="form-control" required></div>
                                            <div class="col-12"><label>Description</label><textarea name="description" rows="4" class="form-control"></textarea></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="border rounded p-3 mb-3">
                                            <label>Thumbnail</label>
                                            <input type="file" name="thumbnail" class="form-control mb-2" accept="image/*">
                                            <img id="thumbnail_preview" src="" class="img-fluid rounded" style="max-height:180px; display:none;">
                                        </div>
                                        <div class="mb-3">
                                            <label>Gallery Images</label>
                                            <input type="file" name="images[]" multiple class="form-control" accept="image/*">
                                        </div>
                                        <div class="mb-3">
                                            <label>Brand</label>
                                            <select name="brand_id" class="form-control">
                                                <option value="">No Brand</option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Category</label>
                                            <select name="category_id" class="form-control">
                                                <option value="">Select Category</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                    @foreach($cat->children as $child)
                                                        <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                                    @endforeach
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                            <label class="form-check-label" for="is_featured">Featured Product</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">Save Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';

    const list = new List('productList', {
        valueNames: ['title', 'category', 'stock', 'price', 'sold', 'created_at', 'featured'],
        page: 12,
        pagination: true
    });

    // Bulk delete
    document.getElementById('checkAll')?.addEventListener('change', function () {
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => cb.checked = this.checked);
        toggleBulk();
    });
    function toggleBulk() {
        const checked = document.querySelectorAll('input[name="chk_child"]:checked').length;
        document.getElementById('remove-actions').classList.toggle('d-none', checked === 0);
    }
    window.deleteMultiple = function () {
        const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked')).map(cb => cb.value);
        if (!ids.length) return;
        Swal.fire({
            title: `Delete ${ids.length} products?`,
            icon: 'warning',
            showCancelButton: true,
        }).then(r => {
            if (r.isConfirmed) {
                Promise.all(ids.map(id => axios.delete(`/products/${id}`))).then(() => location.reload());
            }
        });
    };

    // Edit
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            axios.get(`/products/${id}/edit`).then(res => {
                const p = res.data;
                document.getElementById('product_id').value = p.id;
                document.querySelector('[name="title"]').value = p.title;
                document.querySelector('[name="sku"]').value = p.sku;
                document.querySelector('[name="price"]').value = p.price;
                document.querySelector('[name="sale_price"]').value = p.sale_price || '';
                document.querySelector('[name="stock"]').value = p.stock;
                document.querySelector('[name="description"]').value = p.description || '';
                document.querySelector('[name="brand_id"]').value = p.brand_id || '';
                document.querySelector('[name="category_id"]').value = p.category_id || '';
                document.querySelector('[name="is_featured"]').checked = p.is_featured;

                const preview = document.getElementById('thumbnail_preview');
                if (p.thumbnail) {
                    preview.src = p.thumbnail;
                    preview.style.display = 'block';
                } else preview.style.display = 'none';

                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('submitBtn').textContent = 'Update Product';
            });
        });
    });

    // Submit form
    document.getElementById('productForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('product_id').value;
        const url = id ? `/products/${id}` : '/products';
        const data = new FormData(this);
        if (id) data.append('_method', 'PUT');

        axios.post(url, data)
            .then(() => location.reload())
            .catch(err => Swal.fire('Error!', err.response?.data?.message || 'Something went wrong', 'error'));
    });

    // Delete single
    document.querySelectorAll('.remove-item-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            Swal.fire({
                title: 'Delete product?',
                icon: 'warning',
                showCancelButton: true,
            }).then(r => {
                if (r.isConfirmed) axios.delete(`/products/${id}`).then(() => location.reload());
            });
        });
    });
});
</script>
@endsection