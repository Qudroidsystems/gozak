{{-- resources/views/brands/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Brands')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Brands</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">E-commerce</a></li>
                                <li class="breadcrumb-item active">Brands</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Products per Brand</h5></div>
                        <div class="card-body">
                            <canvas id="brandChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brands Table -->
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">All Brands</h5>
                    @can('Create brand')
                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                            <i class="bi bi-plus-circle align-baseline me-1"></i> Add Brand
                        </button>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                            <thead class="text-muted table-light">
                                <tr>
                                    <th>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="checkAll">
                                            <label class="form-check-label" for="checkAll"></label>
                                        </div>
                                    </th>
                                    <th scope="col" class="sort cursor-pointer" data-sort="brand_name">Brand Name</th>
                                    <th scope="col">Logo</th>
                                    <th scope="col" class="sort cursor-pointer" data-sort="categories">Categories</th>
                                    <th scope="col" class="sort cursor-pointer" data-sort="products">Products</th>
                                    <th scope="col" class="sort cursor-pointer" data-sort="featured">Featured</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($data as $brand)
                                <tr>
                                    <th>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $brand->id }}">
                                            <label class="form-check-label"></label>
                                        </div>
                                    </th>
                                    <td class="brand_name">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0">{{ $brand->name }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($brand->logo)
                                            <img src="{{ asset('storage/'.$brand->logo) }}" alt="" class="avatar-md rounded-circle">
                                        @else
                                            <div class="avatar-md bg-light rounded-circle d-flex align-items-center justify-content-center">
                                                <i class="bi bi-image text-muted fs-3"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="categories">
                                        @if($brand->categories->count())
                                            @foreach($brand->categories->take(3) as $cat)
                                                <span class="badge bg-info-subtle text-info me-1">{{ $cat->name }}</span>
                                            @endforeach
                                            @if($brand->categories->count() > 3) <small class="text-muted">+{{ $brand->categories->count()-3 }}</small>@endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="products">
                                        <span class="badge bg-success">{{ $brand->products_count }}</span>
                                    </td>
                                    <td class="featured">
                                        <span class="badge {{ $brand->is_featured ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ $brand->is_featured ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td>
                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                            @can('Update brand')
                                            <li>
                                                <a href="javascript:void(0)" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{ $brand->id }}">
                                                    <i class="ph-pencil"></i>
                                                </a>
                                            </li>
                                            @endcan
                                            @can('Delete brand')
                                            <li>
                                                <a href="#deleteRecordModal" data-bs-toggle="modal" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $brand->id }}">
                                                    <i class="ph-trash"></i>
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr class="noresult">
                                    <td colspan="7">
                                        <div class="text-center py-4">
                                            <i class="ph-magnifying-glass fs-1 text-primary"></i>
                                            <h5 class="mt-2">Sorry! No Result Found</h5>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="noresult" style="display: none">
                            <div class="text-center py-4">
                                <i class="ph-magnifying-glass fs-1 text-primary"></i>
                                <h5 class="mt-2">Sorry! No Result Found</h5>
                                <p class="text-muted mb-0">We've searched all brands. No matches found.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center justify-content-sm-end mt-2">
                        <div class="pagination-wrap hstack gap-2">
                            <a class="page-item pagination-prev {{ $data->onFirstPage() ? 'disabled' : '' }}" href="{{ $data->previousPageUrl() }}">
                                <i class="mdi mdi-chevron-left align-middle"></i>
                            </a>
                            <ul class="pagination listjs-pagination mb-0">
                                @foreach($data->links()->elements[0] as $page => $url)
                                    @if($page == $data->currentPage())
                                        <li class="page-item active"><a class="page-link" href="javascript:void(0)">{{ $page }}</a></li>
                                    @else
                                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                            <a class="page-item pagination-next {{ $data->hasMorePages() ? '' : 'disabled' }}" href="{{ $data->nextPageUrl() }}">
                                <i class="mdi mdi-chevron-right align-middle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="brandForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="brand_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <img id="logo_preview" class="mt-2 rounded" style="max-height:100px;display:none;">
                    </div>
                    <div class="mb-3">
                        <label>Categories</label>
                        <select name="categories[]" id="category_select" class="form-control" multiple>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="is_featured">
                        <label class="form-check-label">Featured</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-md-5">
                <div class="text-center">
                    <div class="text-danger">
                        <i class="bi bi-trash display-4"></i>
                    </div>
                    <div class="mt-4">
                        <h3>Are you sure?</h3>
                        <p class="text-muted">You want to remove this brand?</p>
                    </div>
                </div>
                <div class="hstack gap-2 justify-content-center mt-4">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="delete-record">Yes, Delete It!</button>
                </div>
            </div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

<script>
document.addEventListener('DOMContentLoaded', () => {
    new Chart(document.getElementById('brandChart'), {
        type: 'bar',
        data: { labels: @json($chart_labels), datasets: [{ data: @json($chart_data), backgroundColor: '#405189' }] },
        options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });

    const form = document.getElementById('brandForm');
    const modal = new bootstrap.Modal('#showModal');
    const logoPreview = document.getElementById('logo_preview');
    let choices = new Choices('#category_select', { removeItemButton: true });

    // Add
    document.querySelector('.add-btn')?.addEventListener('click', () => {
        form.reset();
        document.getElementById('brand_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Brand';
        logoPreview.style.display = 'none';
        choices.setValue([]);
    });

    // Edit
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            axios.get(`/brands/${id}/edit`).then(res => {
                const b = res.data;
                document.getElementById('brand_id').value = b.id;
                form.name.value = b.name;
                document.getElementById('is_featured').checked = b.is_featured;
                if (b.logo) {
                    logoPreview.src = b.logo;
                    logoPreview.style.display = 'block';
                }
                choices.setValue(b.categories.map(c => ({ value: c.id, label: c.name })));
                document.getElementById('modalTitle').textContent = 'Edit Brand';
                modal.show();
            });
        });
    });

    // Delete
    let deleteId = null;
    document.querySelectorAll('.remove-item-btn').forEach(btn => {
        btn.addEventListener('click', () => deleteId = btn.dataset.id);
    });
    document.getElementById('delete-record').addEventListener('click', () => {
        axios.delete(`/brands/${deleteId}`).then(() => location.reload());
    });

    // Submit
    form.addEventListener('submit', e => {
        e.preventDefault();
        const id = document.getElementById('brand_id').value;
        const url = id ? `/brands/${id}` : '/brands';
        const fd = new FormData(form);
        if (id) fd.append('_method', 'PUT');
        axios.post(url, fd).then(() => location.reload());
    });
});
</script>
@endsection