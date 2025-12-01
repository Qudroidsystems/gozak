@extends('layouts.master')

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
            <!-- End Page Title -->

            <!-- Products per Brand Chart -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Products per Brand</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="brandChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brands Table -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">
                                Brands <span class="badge bg-dark-subtle text-dark ms-1">{{ $data->total() }}</span>
                            </h5>
                            <div class="flex-shrink-0">
                                @can('Create brand')
                                    <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                                        <i class="bi bi-plus-circle align-baseline me-1"></i> Add Brand
                                    </button>
                                @endcan
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0" id="brandTable">
                                    <thead class="table-active">
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Logo</th>
                                            <th>Categories</th>
                                            <th>Products</th>
                                            <th>Featured</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @forelse($data as $brand)
                                            <tr>
                                                <td>{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}</td>
                                                <td class="name"><strong>{{ $brand->name }}</strong></td>
                                                <td>
                                                    @if($brand->logo)
                                                        <img src="{{ asset(Storage::url($brand->logo)) }}"
                                                             alt="{{ $brand->name }}"
                                                             class="avatar-sm rounded-circle object-fit-cover">
                                                    @else
                                                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-image fs-22 text-muted"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="categories">
                                                    @if($brand->categories->count() > 0)
                                                        @foreach($brand->categories as $cat)
                                                            <span class="badge bg-info-subtle text-info me-1">{{ $cat->name }}</span>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">{{ $brand->products_count }}</span>
                                                </td>
                                                <td>
                                                    @if($brand->is_featured)
                                                        <span class="badge bg-success">Yes</span>
                                                    @else
                                                        <span class="badge bg-secondary">No</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="hstack gap-2">
                                                        @can('Update brand')
                                                            <button type="button"
                                                                    class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"
                                                                    data-id="{{ $brand->id }}">
                                                                <i class="ph-pencil"></i>
                                                            </button>
                                                        @endcan
                                                        @can('Delete brand')
                                                            <button type="button"
                                                                    class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn"
                                                                    data-id="{{ $brand->id }}">
                                                                <i class="ph-trash"></i>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">No brands found</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Table -->

        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="brandForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="brandId">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Logo <span class="text-danger">*</span></label>
                        <input type="file" name="logo" class="form-control" accept="image/*" id="logoInput">
                        <div class="mt-2">
                            <img id="logoPreview" class="rounded" style="max-height: 100px; display: none;" alt="Preview">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categories</label>
                        <select name="categories[]" class="form-control" id="categorySelect" multiple>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="is_featured">
                        <label class="form-check-label" for="is_featured">Mark as Featured</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-5">
                <i class="bi bi-trash text-danger display-4"></i>
                <h4 class="mt-4">Are you sure?</h4>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Chart
    const ctx = document.getElementById('brandChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chart_labels),
            datasets: [{
                label: 'Products',
                data: @json($chart_data),
                backgroundColor: '#405189',
                borderColor: '#405189',
                borderWidth: 1
            }]
        },
        options: {
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { display: false } }
        }
    });

    const modal = new bootstrap.Modal(document.getElementById('addBrandModal'));
    const form = document.getElementById('brandForm');
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');
    let choicesInstance = null;

    // Logo Preview
    logoInput.addEventListener('change', function(e) {
        if (e.target.files[0]) {
            logoPreview.src = URL.createObjectURL(e.target.files[0]);
            logoPreview.style.display = 'block';
        }
    });

    // Open Add Modal
    document.querySelector('.add-btn')?.addEventListener('click', function() {
        form.reset();
        document.getElementById('modalTitle').textContent = 'Add Brand';
        document.getElementById('saveBtn').textContent = 'Save Brand';
        document.getElementById('brandId').value = '';
        logoPreview.style.display = 'none';
        if (choicesInstance) choicesInstance.destroy();
        choicesInstance = new Choices('#categorySelect', { removeItemButton: true });
    });

    // Edit Button
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            axios.get(`/brands/${id}/edit`)
                .then(response => {
                    const b = response.data;
                    document.getElementById('brandId').value = b.id;
                    form.name.value = b.name;
                    document.getElementById('is_featured').checked = b.is_featured;

                    if (b.logo) {
                        logoPreview.src = b.logo;
                        logoPreview.style.display = 'block';
                    } else {
                        logoPreview.style.display = 'none';
                    }

                    if (choicesInstance) choicesInstance.destroy();
                    choicesInstance = new Choices('#categorySelect', { removeItemButton: true });
                    choicesInstance.setValue(b.categories.map(c => ({ value: c.id, label: c.name })));

                    document.getElementById('modalTitle').textContent = 'Edit Brand';
                    document.getElementById('saveBtn').textContent = 'Update Brand';
                    modal.show();
                })
                .catch(() => Swal.fire('Error', 'Failed to load brand data', 'error'));
        });
    });

    // Form Submit (Add & Update)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('brandId').value;
        const url = id ? `/brands/${id}` : '/brands';
        const method = id ? 'PUT' : 'POST';

        const formData = new FormData(form);
        if (method === 'PUT') formData.append('_method', 'PUT');

        axios.post(url, formData)
            .then(() => {
                location.reload();
            })
            .catch(err => {
                let msg = 'Something went wrong';
                if (err.response?.status === 422) {
                    msg = Object.values(err.response.data.errors).flat().join('<br>');
                } else if (err.response?.data?.message) {
                    msg = err.response.data.message;
                }
                Swal.fire('Error!', msg, 'error');
            });
    });

    // Delete
    let deleteId = null;
    document.querySelectorAll('.remove-item-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteId = this.dataset.id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', () => {
        axios.delete(`/brands/${deleteId}`)
            .then(() => location.reload())
            .catch(() => Swal.fire('Error', 'Failed to delete brand', 'error'));
    });
});
</script>
@endsection