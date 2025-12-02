{{-- resources/views/brands/index.blade.php --}}
@extends('layouts.master')

@section('title', 'Brands Management')

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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Brands ({{ $data->total() }})</h5>
                    @can('Create brand')
                        <button type="button" class="btn btn-primary" id="addBrandBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Brand
                        </button>
                    @endcan
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
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
                            <tbody>
                                @forelse($data as $brand)
                                    <tr>
                                        <td>{{ $loop->iteration + ($data->currentPage()-1)*$data->perPage() }}</td>
                                        <td><strong>{{ $brand->name }}</strong></td>
                                        <td>
                                            @if($brand->logo)
                                                <img src="{{ asset('storage/'.$brand->logo) }}" class="rounded avatar-md" alt="{{ $brand->name }}">
                                            @else
                                                <div class="avatar-md bg-light rounded d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-image text-muted fs-2xl"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($brand->categories->count())
                                                @foreach($brand->categories->take(3) as $cat)
                                                    <span class="badge bg-info me-1">{{ $cat->name }}</span>
                                                @endforeach
                                                @if($brand->categories->count() > 3)<small class="text-muted">+{{ $brand->categories->count()-3 }}</small>@endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-success fs-6">{{ $brand->products_count }}</span></td>
                                        <td>
                                            <span class="badge {{ $brand->is_featured ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $brand->is_featured ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td>
                                            @can('Update brand')
                                                <button class="btn btn-sm btn-soft-info edit-btn" data-id="{{ $brand->id }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('Delete brand')
                                                <button class="btn btn-sm btn-soft-danger delete-btn" data-id="{{ $brand->id }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-5 text-muted">No brands found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $data->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ================================== MODALS ================================== --}}
<div class="modal fade" id="brandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="brandForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="brand_id">

                <div class="modal-header">
                    <h5 class="modal-title" id="brandModalLabel">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <input type="file" class="form-control" name="logo" accept="image/*">
                        <div class="mt-2">
                            <img id="logo_preview" class="rounded shadow" style="max-height:120px; display:none;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categories</label>
                        <select class="form-select" name="categories[]" id="categories_select" multiple>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured">
                        <label class="form-check-label">Featured Brand</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="bi bi-trash text-danger" style="font-size:4rem;"></i>
                <h4 class="mt-3">Delete Brand?</h4>
                <p class="text-muted">This action cannot be undone.</p>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>
{{-- ================================== END MODALS ================================== --}}



{{-- ================================== SCRIPTS ================================== --}}

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Chart
    new Chart(document.getElementById('brandChart'), {
        type: 'bar',
        data: {
            labels: @json($chart_labels),
            datasets: [{ label: 'Products', data: @json($chart_data), backgroundColor: '#405189' }]
        },
        options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
    });

    const modal = new bootstrap.Modal('#brandModal');
    const form = document.getElementById('brandForm');
    const logoPreview = document.getElementById('logo_preview');
    let choices = null;

    // Initialize Choices only once
    function initChoices() {
        if (!choices) {
            choices = new Choices('#categories_select', {
                removeItemButton: true,
                searchEnabled: true,
                placeholderValue: 'Select categories...'
            });
        }
    }

    // Reset form
    function resetForm() {
        form.reset();
        document.getElementById('brand_id').value = '';
        document.getElementById('brandModalLabel').textContent = 'Add Brand';
        document.getElementById('submitBtn').textContent = 'Save Brand';
        logoPreview.style.display = 'none';
        if (choices) choices.setValue([]);
    }

    // Add Button
    document.getElementById('addBrandBtn')?.addEventListener('click', () => {
        resetForm();
        initChoices();
        modal.show();
    });

    // Edit Buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            axios.get(`/brands/${id}/edit`)
                .then(res => {
                    const b = res.data;
                    document.getElementById('brand_id').value = b.id;
                    form.name.value = b.name;
                    document.getElementById('is_featured').checked = b.is_featured;

                    if (b.logo) {
                        logoPreview.src = b.logo;
                        logoPreview.style.display = 'block';
                    } else {
                        logoPreview.style.display = 'none';
                    }

                    initChoices();
                    choices.setValue(b.categories.map(c => ({ value: c.id, label: c.name })));

                    document.getElementById('brandModalLabel').textContent = 'Edit Brand';
                    document.getElementById('submitBtn').textContent = 'Update Brand';
                    modal.show();
                })
                .catch(() => alert('Failed to load brand'));
        });
    });

    // Form Submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('brand_id').value;
        const url = id ? `/brands/${id}` : '/brands';

        const formData = new FormData(form);
        if (id) formData.append('_method', 'PUT');

        axios.post(url, formData)
            .then(() => location.reload())
            .catch(err => {
                let msg = 'Something went wrong';
                if (err.response?.status === 422) {
                    msg = Object.values(err.response.data.errors).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
            });
    });

    // Delete
    let deleteId = null;
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            deleteId = btn.dataset.id;
            new bootstrap.Modal('#deleteModal').show();
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', () => {
        axios.delete(`/brands/${deleteId}`)
            .then(() => location.reload())
            .catch(() => Swal.fire('Error', 'Cannot delete brand', 'error'));
    });
});
</script>
@endsection