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
                                <table class="table table-centered align-middle table-nowrap mb-0">
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
                                    <tbody>
                                        @forelse($data as $brand)
                                            <tr>
                                                <td>{{ $loop->iteration + ($data->currentPage()-1)*$data->perPage() }}</td>
                                                <td><strong>{{ $brand->name }}</strong></td>
                                                <td>
                                                    @if($brand->logo)
                                                        <img src="{{ asset(Storage::url($brand->logo)) }}" class="avatar-sm rounded-circle object-fit-cover" alt="{{ $brand->name }}">
                                                    @else
                                                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($brand->categories->count())
                                                        @foreach($brand->categories as $cat)
                                                            <span class="badge bg-info-subtle text-info me-1">{{ $cat->name }}</span>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-success">{{ $brand->products_count }}</span></td>
                                                <td>
                                                    <span class="badge {{ $brand->is_featured ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $brand->is_featured ? 'Yes' : 'No' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="hstack gap-2">
                                                        @can('Update brand')
                                                            <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{ $brand->id }}">
                                                                <i class="ph-pencil"></i>
                                                            </button>
                                                        @endcan
                                                        @can('Delete brand')
                                                            <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $brand->id }}">
                                                                <i class="ph-trash"></i>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center py-5">No brands found</td></tr>
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

        </div>
    </div>
</div>

<!-- Modals (Add/Edit + Delete) -->
@include('brands.partials.modals')

@endsection

{{-- =========================== SCRIPTS (MUST BE OUTSIDE content) =========================== --}}
@section('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Choices.js (for multi-select) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Chart
    new Chart(document.getElementById('brandChart'), {
        type: 'bar',
        data: {
            labels: @json($chart_labels),
            datasets: [{
                label: 'Products',
                data: @json($chart_data),
                backgroundColor: '#405189'
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
    let choices = null;

    // Logo preview
    logoInput.addEventListener('change', e => {
        if (e.target.files[0]) {
            logoPreview.src = URL.createObjectURL(e.target.files[0]);
            logoPreview.style.display = 'block';
        }
    });

    // Add Button
    document.querySelector('.add-btn')?.addEventListener('click', () => {
        form.reset();
        document.getElementById('modalTitle').textContent = 'Add Brand';
        document.getElementById('saveBtn').textContent = 'Save Brand';
        document.getElementById('brandId').value = '';
        logoPreview.style.display = 'none';
        if (choices) choices.destroy();
        choices = new Choices('#categorySelect', { removeItemButton: true });
    });

    // Edit Buttons
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            axios.get(`/brands/${id}/edit`)
                .then(res => {
                    const b = res.data;
                    document.getElementById('brandId').value = b.id;
                    form.name.value = b.name;
                    document.getElementById('is_featured').checked = b.is_featured;

                    if (b.logo) {
                        logoPreview.src = b.logo;
                        logoPreview.style.display = 'block';
                    } else {
                        logoPreview.style.display = 'none';
                    }

                    if (choices) choices.destroy();
                    choices = new Choices('#categorySelect', { removeItemButton: true });
                    choices.setValue(b.categories.map(c => ({ value: c.id, label: c.name })));

                    document.getElementById('modalTitle').textContent = 'Edit Brand';
                    document.getElementById('saveBtn').textContent = 'Update Brand';
                    modal.show();
                });
        });
    });

    // Submit (Add & Update)
    form.addEventListener('submit', e => {
        e.preventDefault();
        const id = document.getElementById('brandId').value;
        const url = id ? `/brands/${id}` : '/brands';

        const formData = new FormData(form);
        if (id) formData.append('_method', 'PUT');

        axios.post(url, formData)
            .then(() => location.reload())
            .catch(err => {
                let msg = 'Error';
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
        btn.addEventListener('click', () => {
            deleteId = btn.dataset.id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
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