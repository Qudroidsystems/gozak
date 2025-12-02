{{-- resources/views/categories/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Categories Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Categories</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">E-commerce</a></li>
                                <li class="breadcrumb-item active">Categories</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Categories by Products</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card" id="categoryList">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">All Categories</h5>
                            <div class="flex-shrink-0">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                        <i class="ri-delete-bin-2-line"></i>
                                    </button>
                                    @can('Create category')
                                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                                            <i class="bi bi-plus-circle me-1"></i> Add Category
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                </div>
                                            </th>
                                            <th>#</th>
                                            <th>Image</th>
                                            <th>Category Name</th>
                                            <th>Parent</th>
                                            <th>Products</th>
                                            <th>Featured</th>
                                            <th width="100">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list form-check-all">
                                        @forelse($categories as $cat)
                                        <tr class="border-bottom border-light-subtle">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $cat->id }}">
                                                </div>
                                            </td>
                                            <td class="fw-medium">{{ $loop->iteration }}</td>
                                            <td>
                                                @if($cat->image)
                                                    <img src="{{ asset('storage/' . $cat->image) }}"
                                                         class="avatar-lg rounded object-fit-cover"
                                                         alt="{{ $cat->name }}"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                                                    <div class="avatar-lg bg-light rounded d-flex align-items-center justify-content-center" style="display:none;">
                                                        <i class="bi bi-image text-muted fs-3"></i>
                                                    </div>
                                                @else
                                                    <div class="avatar-lg bg-light rounded d-flex align-items-center justify-content-center">
                                                        <i class="bi bi-image text-muted fs-3"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td><strong>{{ $cat->name }}</strong></td>
                                            <td>
                                                @if($cat->parent)
                                                    <span class="badge bg-primary-subtle text-primary">{{ $cat->parent->name }}</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info fs-6">{{ $cat->products_count ?? 0 }}</span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $cat->is_featured ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $cat->is_featured ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="hstack gap-2">
                                                    @can('Update category')
                                                        <button class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{ $cat->id }}">
                                                            <i class="ph-pencil"></i>
                                                        </button>
                                                    @endcan
                                                    @can('Delete category')
                                                        <button class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $cat->id }}">
                                                            <i class="ph-trash"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted noresult">No categories found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div class="noresult" style="display:none">
                                    <div class="text-center py-4">
                                        <i class="ph-magnifying-glass fs-1 text-primary"></i>
                                        <h5 class="mt-2">Sorry! No Result Found</h5>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-end mt-4">
                                <div class="pagination-wrap hstack gap-2">
                                    <a class="page-item pagination-prev disabled" href="#"><i class="mdi mdi-chevron-left"></i></a>
                                    <ul class="pagination listjs-pagination mb-0"></ul>
                                    <a class="page-item pagination-next" href="#"><i class="mdi mdi-chevron-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="showModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="categoryForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="category_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select class="form-select" name="parent_id" id="parent_id">
                            <option value="">No Parent (Top Level)</option>
                            @foreach($allCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category Image</label>
                        <input type="file" class="form-control" name="image" accept="image/*">
                        <div class="mt-2">
                            <img id="image_preview" class="rounded shadow-sm" style="max-height:120px; display:none;" alt="Preview">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured">
                        <label class="form-check-label">Featured Category</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_nsfw" value="1" id="is_nsfw">
                        <label class="form-check-label text-danger">NSFW / Adult Content</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteRecordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="bi bi-trash text-danger display-4"></i>
                <h4 class="mt-4">Delete Category?</h4>
                <p class="text-muted">This will also delete all sub-categories and products!</p>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="delete-record">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- ALL JAVASCRIPT – FULLY WORKING -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/list.js@2.3.1/dist/list.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';

    // Chart
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: @json($chart_labels),
            datasets: [{
                data: @json($chart_data),
                backgroundColor: ['#405189','#f1b44c','#34c38f','#556ee6','#f46a6a','#50a5f1','#f1b44c','#6f42c1']
            }]
        },
        options: {
            plugins: { legend: { position: 'right' } }
        }
    });

    // List.js
    new List('categoryList', {
        valueNames: ['id', 'name', 'parent', 'products', 'featured'],
        page: 10,
        pagination: true
    });

    // Checkbox Select All
    const checkAll = document.getElementById('checkAll');
    checkAll?.addEventListener('change', function () {
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
            cb.checked = this.checked;
            cb.closest('tr').classList.toggle('table-active', this.checked);
        });
        toggleRemoveBtn();
    });

    document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
        cb.addEventListener('change', function () {
            this.closest('tr').classList.toggle('table-active', this.checked);
            const all = document.querySelectorAll('input[name="chk_child"]');
            checkAll.checked = Array.from(all).every(c => c.checked);
            toggleRemoveBtn();
        });
    });

    function toggleRemoveBtn() {
        const count = document.querySelectorAll('input[name="chk_child"]:checked').length;
        document.getElementById('remove-actions').classList.toggle('d-none', count === 0);
    }

    const modal = new bootstrap.Modal('#showModal');
    const form = document.getElementById('categoryForm');
    const imgPreview = document.getElementById('image_preview');

    // Add button
    document.querySelector('.add-btn')?.addEventListener('click', () => {
        form.reset();
        document.getElementById('category_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Category';
        document.getElementById('submitBtn').textContent = 'Save Category';
        imgPreview.style.display = 'none';
    });

    // Edit
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            axios.get(`/categories/${id}/edit`)
                .then(res => {
                    const c = res.data;
                    document.getElementById('category_id').value = c.id;
                    form.name.value = c.name;
                    document.getElementById('parent_id').value = c.parent_id || '';
                    document.getElementById('is_featured').checked = c.is_featured;
                    document.getElementById('is_nsfw').checked = c.is_nsfw;

                    if (c.image) {
                        imgPreview.src = c.image;
                        imgPreview.style.display = 'block';
                    } else {
                        imgPreview.style.display = 'none';
                    }

                    document.getElementById('modalTitle').textContent = 'Edit Category';
                    document.getElementById('submitBtn').textContent = 'Update Category';
                    modal.show();
                });
        });
    });

    // Submit
    form.addEventListener('submit', e => {
        e.preventDefault();
        const id = document.getElementById('category_id').value;
        const url = id ? `/categories/${id}` : '/categories';
        const data = new FormData(form);
        if (id) data.append('_method', 'PUT');

        axios.post(url, data)
            .then(() => location.reload())
            .catch(err => {
                let msg = 'Error';
                if (err.response?.status === 422) {
                    msg = Object.values(err.response.data.errors).flat().join('<br>');
                }
                Swal.fire('Error!', msg, 'error');
            });
    });

    // Delete
    let deleteId = null;
    document.querySelectorAll('.remove-item-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            deleteId = btn.dataset.id;
            new bootstrap.Modal('#deleteRecordModal').show();
        });
    });

    document.getElementById('delete-record').addEventListener('click', () => {
        axios.delete(`/categories/${deleteId}`)
            .then(() => location.reload())
            .catch(() => Swal.fire('Error', 'Cannot delete', 'error'));
    });

    // Multiple Delete
    window.deleteMultiple = function () {
        const ids = Array.from(document.querySelectorAll('input[name="chk_child"]:checked'))
            .map(cb => cb.value);
        if (!ids.length) return;
        Swal.fire({
            title: 'Delete selected?',
            icon: 'warning',
            showCancelButton: true
        }).then(r => {
            if (r.isConfirmed) {
                Promise.all(ids.map(id => axios.delete(`/categories/${id}`)))
                    .then(() => location.reload());
            }
        });
    };

    // Image preview
    form.querySelector('[name="image"]').addEventListener('change', e => {
        const file = e.target.files[0];
        if (file) {
            imgPreview.src = URL.createObjectURL(file);
            imgPreview.style.display = 'block';
        }
    });
});
</script>
@endsection