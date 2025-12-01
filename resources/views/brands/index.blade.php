@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Brands</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">E-commerce</a></li>
                                <li class="breadcrumb-item active">Brands</li>
                            ol>
                        div>
                    div>
                div>
            div>

            <!-- Chart -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Products per Brand</h5>
                        div>
                        <div class="card-body">
                            <canvas id="brandChart" height="100">canvas>
                        div>
                    div>
                div>
            div>

            <div id="brandList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h5 class="card-title mb-0 flex-grow-1">Brands <span class="badge bg-dark-subtle text-dark ms-1">{{$data->total()}}span>h5>
                                <div class="flex-shrink-0">
                                    @can('Create brand')
                                        <button class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                                            <i class="bi bi-plus-circle me-1">i> Add Brand
                                        button>
                                    @endcan
                                div>
                            div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="brandTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th>#</th>
                                                <th class="sort" data-sort="name">Name</th>
                                                <th>Logo</th>
                                                <th>Categories</th>
                                                <th>Products</th>
                                                <th>Featured</th>
                                                <th>Action</th>
                                            tr>
                                        thead>
                                        <tbody class="list form-check-all">
                                            @foreach($data as $i => $brand)
                                            <tr>
                                                <td>{{ $loop->iteration + ($data->currentPage() - 1) * $data->perPage() }}td>
                                                <td class="name"> <strong>{{$brand->name}}strong> td>
                                                <td>
                                                    @if($brand->logo)
                                                        <img src="{{ asset(Storage::url($brand->logo)) }}" alt="" class="avatar-sm rounded">
                                                    @else
                                                        <div class="avatar-sm bg-light rounded">
                                                            <i class="bi bi-image fs-22">i>
                                                        div>
                                                    @endif
                                                td>
                                                <td class="categories">
                                                    @if($brand->categories->count())
                                                        @foreach($brand->categories as $cat)
                                                            <span class="badge bg-info-subtle text-info me-1">{{$cat->name}}span>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">—span>
                                                    @endif
                                                td>
                                                <td>
                                                    <span class="badge bg-success">{{$brand->products_count}}span>
                                                td>
                                                <td>
                                                    @if($brand->is_featured)
                                                        <span class="badge bg-success">Yesspan>
                                                    @else
                                                        <span class="badge bg-secondary">Nospan>
                                                    @endif
                                                td>
                                                <td>
                                                    <div class="hstack gap-2">
                                                        @can('Update brand')
                                                            <button class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" data-id="{{$brand->id}}">
                                                                <i class="ph-pencil">i>
                                                            button>
                                                        @endcan
                                                        @can('Delete brand')
                                                            <button class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{$brand->id}}">
                                                                <i class="ph-trash">i>
                                                            button>
                                                        @endcan
                                                    div>
                                                td>
                                            tr>
                                            @endforeach
                                        tbody>
                                    table>
                                div>

                                {!! $data->links() !!}

                            div>
                        div>
                    div>
                div>
            div>
        div>
    div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="addBrandModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="brandForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="brandId">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Brandh5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal">button>
                    div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        div>
                        <div class="mb-3">
                            <label>Logo *</label>
                            <input type="file" name="logo" class="form-control" accept="image/*" id="logoInput">
                            <img id="logoPreview" class="mt-2 rounded" style="max-height:100px; display:none;">
                        div>
                        <div class="mb-3">
                            <label>Categories</label>
                            <select name="categories[]" class="form-control" multiple id="categorySelect">
                                @foreach($categories as $id => $name)
                                    <option value="{{$id}}">{{$name}}option>
                                @endforeach
                            select>
                        div>
                        <div class="form-check">
                            <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="is_featured">
                            <label class="form-check-label">Is Featured?label>
                        div>
                    div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Closebutton>
                        <button type="submit" class="btn btn-primary" id="saveBtn">Save Brandbutton>
                    div>
                form>
            div>
        div>
    div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <i class="bi bi-trash text-danger display-5">i>
                    <h4 class="mt-3">Delete Brand?h4>
                    <p class="text-muted">This action cannot be undone.p>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelbutton>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Yes, Deletebutton>
                div>
            div>
        div>
    div>
div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        options: { scales: { y: { beginAtZero: true } } }
    });

    const modal = new bootstrap.Modal(document.getElementById('addBrandModal'));
    const form = document.getElementById('brandForm');
    const logoInput = document.getElementById('logoInput');
    const logoPreview = document.getElementById('logoPreview');

    logoInput?.addEventListener('change', e => {
        if (e.target.files[0]) {
            logoPreview.src = URL.createObjectURL(e.target.files[0]);
            logoPreview.style.display = 'block';
        }
    });

    // Add Brand
    document.querySelector('.add-btn')?.addEventListener('click', () => {
        form.reset();
        document.getElementById('modalTitle').textContent = 'Add Brand';
        document.getElementById('saveBtn').textContent = 'Save Brand';
        document.getElementById('brandId').value = '';
        logoPreview.style.display = 'none';
        new Choices('#categorySelect', { removeItemButton: true });
    });

    // Edit
    document.querySelectorAll('.edit-item-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            axios.get(`/brands/${id}/edit`).then(res => {
                const b = res.data;
                document.getElementById('brandId').value = b.id;
                form.name.value = b.name;
                form.is_featured.checked = b.is_featured;
                logoPreview.src = b.logo;
                logoPreview.style.display = b.logo ? 'block' : 'none';

                const choices = new Choices('#categorySelect', { removeItemButton: true });
                choices.setValue(b.categories.map(c => ({ value: c.id, label: c.name })));

                document.getElementById('modalTitle').textContent = 'Edit Brand';
                document.getElementById('saveBtn').textContent = 'Update Brand';
                modal.show();
            });
        });
    });

    // Save / Update
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const id = document.getElementById('brandId').value;
        const url = id ? `/brands/${id}` : '/brands';
        const method = id ? 'put' : 'post';

        const formData = new FormData(form);
        if (method === 'put') formData.append('_method', 'PUT');

        axios.post(url, formData)
            .then(() => location.reload())
            .catch(err => {
                let msg = err.response?.data?.message || 'Error';
                if (err.response?.status === 422) {
                    msg = Object.values(err.response.data.errors).flat().join('<br>');
                }
                Swal.fire('Error', msg, 'error');
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
        axios.delete(`/brands/${deleteId}`).then(() => location.reload());
    });
});
script>
@endsection