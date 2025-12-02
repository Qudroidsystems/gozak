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

            <!-- Brands List Card -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card" id="brandList">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">All Brands</h5>
                            <div class="flex-shrink-0">
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-danger d-none" id="remove-actions" onclick="deleteMultiple()">
                                        <i class="ri-delete-bin-2-line"></i>
                                    </button>
                                    @can('Create brand')
                                        <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal">
                                            <i class="bi bi-plus-circle align-baseline me-1"></i> Add Brand
                                        </button>
                                    @endcan
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive table-card">
                                <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                                    <thead class="text-muted table-light">
                                        <tr>
                                            <th scope="col">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                    <label class="form-check-label" for="checkAll"></label>
                                                </div>
                                            </th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="id">#</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="name">Brand Name</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="logo">Logo</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="categories">Categories</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="products">Products</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="featured">Featured</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list form-check-all">
                                        @forelse($data as $brand)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="chk_child" value="{{ $brand->id }}">
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </td>
                                                <td class="id"><a href="javascript:void(0)" class="fw-medium">{{ $brand->id }}</a></td>
                                                <td class="name">{{ $brand->name }}</td>
                                                <td class="logo">
                                                    @if($brand->logo)
                                                        <img src="{{ asset('storage/'.$brand->logo) }}" alt="" class="avatar-sm rounded-circle">
                                                    @else
                                                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="categories">
                                                    @if($brand->categories->count())
                                                        @foreach($brand->categories->take(3) as $cat)
                                                            <span class="badge bg-info-subtle text-info me-1">{{ $cat->name }}</span>
                                                        @endforeach
                                                        @if($brand->categories->count() > 3)
                                                            <span class="text-muted">+{{ $brand->categories->count() - 3 }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="products">{{ $brand->products_count }}</td>
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
                                                                <a href="javascript:void(0)" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" data-id="{{ $brand->id }}">
                                                                    <i class="ph-trash"></i>
                                                                </a>
                                                            </li>
                                                        @endcan
                                                    </ul>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="noresult" style="display: none;">
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="ph-magnifying-glass fs-1 text-primary"></i>
                                                    <h5 class="mt-2">Sorry! No Result Found</h5>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div class="noresult" style="display: none">
                                    <div class="text-center py-4">
                                        <i class="ph-magnifying-glass fs-1 text-primary"></i>
                                        <h5 class="mt-2">Sorry! No Result Found</h5>
                                        <p class="text-muted mb-0">We've searched all brands and found nothing matching your criteria.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- List.js Pagination -->
                            <div class="d-flex justify-content-center justify-content-sm-end mt-4">
                                <div class="pagination-wrap hstack gap-2">
                                    <a class="page-item pagination-prev disabled" href="javascript:void(0)">
                                        <i class="mdi mdi-chevron-left align-middle"></i>
                                    </a>
                                    <ul class="pagination listjs-pagination mb-0"></ul>
                                    <a class="page-item pagination-next" href="javascript:void(0)">
                                        <i class="mdi mdi-chevron-right align-middle"></i>
                                    </a>
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
                        <label>Brand Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label>Logo</label>
                        <input type="file" class="form-control" name="logo" accept="image/*">
                        <div class="mt-2">
                            <img id="logo_preview" class="rounded" style="max-height:120px; display:none;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Categories</label>
                        <select class="form-select" name="categories[]" id="categories_select" multiple>
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured">
                        <label class="form-check-label">Featured Brand</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Brand</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteRecordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <i class="bi bi-trash text-danger display-4"></i>
                <h4 class="mt-4">Are you sure?</h4>
                <p class="text-muted">You won't be able to revert this!</p>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="delete-record">Yes, Delete It!</button>
            </div>
        </div>
    </div>
</div>
<script>
    window.chartLabels = @json($chart_labels);
    window.chartData = @json($chart_data);
</script>
@endsection

