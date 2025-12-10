@extends('layouts.master')
@section('title', 'Customer Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Customer Management</h4>
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Ecommerce</a></li>
                            <li class="breadcrumb-item active">Customers</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-primary-subtle">
                        <div class="card-body">
                            <p class="text-uppercase fw-medium text-primary mb-0">Total Customers</p>
                            <h4 class="fs-22 fw-semibold mb-0">{{ number_format($stats['total']) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-success-subtle">
                        <div class="card-body">
                            <p class="text-uppercase fw-medium text-success mb-0">Active</p>
                            <h4 class="fs-22 fw-semibold mb-0">{{ $stats['active'] }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-warning-subtle">
                        <div class="card-body">
                            <p class="text-uppercase fw-medium text-warning mb-0">Total Spent</p>
                            <h4 class="fs-22 fw-semibold mb-0">${{ number_format($stats['total_spent'], 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate bg-danger-subtle">
                        <div class="card-body">
                            <p class="text-uppercase fw-medium text-danger mb-0">Blocked</p>
                            <h4 class="fs-22 fw-semibold mb-0">{{ $stats['blocked'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Export -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('customers.index') }}" method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <input type="text" name="search" class="form-control" placeholder="Search customer..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary me-2">Search</button>
                                    <a href="{{ route('customers.export') }}" class="btn btn-success">Export Excel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="row mt-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>All Customers</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Orders</th>
                                            <th>Total Spent</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customers as $customer)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3">
                                                        <div class="avatar-title bg-primary-subtle rounded-circle">
                                                            {{ Str::substr($customer->first_name, 0, 1) }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0">{{ $customer->first_name }} {{ $customer->last_name }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $customer->email }}</td>
                                            <td>{{ $customer->orders_count }}</td>
                                            <td>${{ number_format($customer->orders_sum_total_amount ?? 0, 2) }}</td>
                                            <td>
                                                <span class="badge {{ $customer->status == 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                    {{ ucfirst($customer->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $customer->created_at->format('d M Y') }}</td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6" class="text-center py-5">No customers found</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {!! $customers->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection