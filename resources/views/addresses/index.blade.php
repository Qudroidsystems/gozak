@extends('layouts.master')

@section('title', 'Address Management')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Addresses</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">All Addresses</h5>
                            <a href="{{ route('addresses.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Add Address
                            </a>
                        </div>
                        <div class="card-body">

                            <form action="{{ route('addresses.index') }}" method="GET" class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control" placeholder="Search address or customer..." value="{{ request('search') }}">
                                </div>
                                <div class="col-md-3">
                                    <select name="customer_id" class="form-select">
                                        <option value="">All Customers</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->first_name }} {{ $customer->last_name }} ({{ $customer->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap">
                                    <thead class="table-active">
                                        <tr>
                                            <th>Customer</th>
                                            <th>Address</th>
                                            <th>Phone</th>
                                            <th>Default</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($addresses as $address)
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong>{{ $address->user->first_name }} {{ $address->user->last_name }}</strong><br>
                                                    <small class="text-muted">{{ $address->user->email }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                @if($address->name)<strong>{{ $address->name }}</strong><br>@endif
                                                {{ $address->street }}<br>
                                                {{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}<br>
                                                <small>{{ $address->country }}</small>
                                            </td>
                                            <td>{{ $address->phone_number }}</td>
                                            <td>
                                                @if($address->is_default)
                                                    <span class="badge bg-success">Default</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-subtle-secondary btn-sm btn-icon" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="{{ route('addresses.show', $address) }}">View</a></li>
                                                        <li><a class="dropdown-item" href="{{ route('addresses.edit', $address) }}">Edit</a></li>
                                                        <li>
                                                            <form action="{{ route('addresses.destroy', $address) }}" method="POST" style="display:inline">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Delete this address?')">Delete</button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No addresses found.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {!! $addresses->appends(request()->query())->links('pagination::bootstrap-5') !!}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
