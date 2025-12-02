{{-- resources/views/banners/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Banners Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Banners</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Marketing</a></li>
                                <li class="breadcrumb-item active">Banners</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banners Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card" id="bannerList">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">All Banners</h5>
                            <div class="flex-shrink-0">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                        Delete Selected
                                    </button>
                                    @can('Create banner')
                                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                                            Add Banner
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
                                            <th>Target Screen</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th width="120">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list form-check-all">
                                        @forelse($banners as $banner)
                                        <tr class="border-bottom border-light-subtle">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $banner->id }}">
                                                </div>
                                            </td>
                                            <td class="fw-medium">{{ $loop->iteration }}</td>
                                            <td>
                                                @if($banner->image_url && file_exists(public_path('storage/' . $banner->image_url)))
                                                    <img src="{{ asset('storage/' . $banner->image_url) }}"
                                                         class="rounded shadow-sm"
                                                         style="width: 240px; height: 120px; object-fit: cover;"
                                                         alt="Banner">
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                         style="width:240px;height:120px;">
                                                        <i class="bi bi-image text-muted fs-3"></i>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info fs-6">
                                                    {{ ucwords(str_replace('_', ' ', $banner->target_screen)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $banner->active ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $banner->active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>{{ $banner->created_at->format('d M Y') }}</td>
                                            <td>
                                                <div class="hstack gap-2">
                                                    @can('Update banner')
                                                        <button class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{ $banner->id }}">
                                                            <i class="ph-pencil"></i>
                                                        </button>
                                                    @endcan
                                                    @can('Delete banner')
                                                        <button class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $banner->id }}">
                                                            <i class="ph-trash"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">No banners found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <div class="pagination-wrap hstack gap-2">
                                    <a class="page-item pagination-prev {{ $banners->onFirstPage() ? 'disabled' : '' }}"
                                       href="{{ $banners->previousPageUrl() }}">Previous</a>
                                    <span class="px-3 py-2 bg-light rounded">{{ $banners->currentPage() }} / {{ $banners->lastPage() ?: 1 }}</span>
                                    <a class="page-item pagination-next {{ $banners->hasMorePages() ? '' : 'disabled' }}"
                                       href="{{ $banners->nextPageUrl() }}">Next</a>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="bannerForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="banner_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="mb-3">
                                <label class="form-label">Banner Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <small class="text-muted">Recommended: 1200×600px or larger</small>
                            </div>
                            <div class="text-center">
                                <img id="image_preview" class="rounded shadow" style="max-width:100%; max-height:300px; display:none;" alt="Preview">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="mb-3">
                                <label class="form-label">Target Screen</label>
                                <select class="form-select" name="target_screen" required>
                                    <option value="home">Home Screen</option>
                                    <option value="category">Category Page</option>
                                    <option value="product">Product Detail</option>
                                    <option value="offers">Offers Page</option>
                                    <option value="all">All Pages</option>
                                </select>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="active" value="1" id="active" checked>
                                <label class="form-check-label">Active (Visible)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Banner</button>
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
                <h4 class="mt-4">Delete Banner?</h4>
                <p class="text-muted">This action cannot be undone.</p>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="delete-record">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- ONLY NEEDED SCRIPTS - CDN -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = '{{ csrf_token() }}';

    // List.js
    new List('bannerList', {
        valueNames: ['target_screen', 'active'],
        page: 10,
        pagination: true
    });

    // Checkbox logic
    const checkAll = document.getElementById('checkAll');
    checkAll?.addEventListener('change', function () {
        document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
            cb.checked = this.checked;
            cb.closest('tr')?.classList.toggle('table-active', this.checked);
        });
        toggleRemoveBtn();
    });

    document.querySelectorAll('input[name="chk_child"]').forEach(cb => {
        cb.addEventListener('change', () => {
            cb.closest('tr')?.classList.toggle('table-active', cb.checked);
            toggleRemoveBtn();
        });
    });

    function toggleRemoveBtn() {
        const count = document.querySelectorAll('input[name="chk_child"]:checked').length;
        document.getElementById('remove-actions').classList.toggle('d-none', count === 0);
    }

    const modal = new bootstrap.Modal('#showModal');
    const form = document.getElementById('bannerForm');
    const imgPreview = document.getElementById('image_preview');

    // Add button
    document.querySelector('.add-btn')?.addEventListener('click', () => {
        form.reset();
        document.getElementById('banner_id').value = '';
        document.getElementById('modalTitle').textContent = 'Add Banner';
        document.getElementById('submitBtn').textContent = 'Save Banner';
        imgPreview.style.display = 'none';
    });

    // Edit
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            axios.get(`/banners/${id}/edit`)
                .then(res => {
                    const b = res.data;
                    document.getElementById('banner_id').value = b.id;
                    form.querySelector('[name="target_screen"]').value = b.target_screen;
                    document.getElementById('active').checked = b.active;

                    if (b.image_url) {
                        imgPreview.src = b.image_url;
                        imgPreview.style.display = 'block';
                    } else {
                        imgPreview.style.display = 'none';
                    }

                    document.getElementById('modalTitle').textContent = 'Edit Banner';
                    document.getElementById('submitBtn').textContent = 'Update Banner';
                    modal.show();
                });
        });
    });

    // Image preview
    form.querySelector('[name="image"]').addEventListener('change', e => {
        const file = e.target.files[0];
        if (file) {
            imgPreview.src = URL.createObjectURL(file);
            imgPreview.style.display = 'block';
        }
    });

    // Submit
    form.addEventListener('submit', e => {
        e.preventDefault();
        const id = document.getElementById('banner_id').value;
        const url = id ? `/banners/${id}` : '/banners';
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
        axios.delete(`/banners/${deleteId}`)
            .then(() => location.reload())
            .catch(() => Swal.fire('Error', 'Cannot delete', 'error'));
    });

    // Multiple delete
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
                Promise.all(ids.map(id => axios.delete(`/banners/${id}`)))
                    .then(() => location.reload());
            }
        });
    };
});
</script>
@endsection