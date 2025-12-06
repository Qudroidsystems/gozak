@extends('layouts.master')

@section('title', 'Inventory Dashboard')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Inventory Dashboard' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY STATS -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Products</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($totalProducts) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle rounded-circle fs-3">
                                        <i class="bi bi-box text-primary"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Locations</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($totalLocations) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle rounded-circle fs-3">
                                        <i class="bi bi-shop text-success"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Low Stock Items</p>
                                    <h4 class="fs-22 fw-semibold mb-0">{{ number_format($lowStockProducts->count()) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle rounded-circle fs-3">
                                        <i class="bi bi-exclamation-triangle text-warning"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-uppercase fw-medium text-muted mb-0">Total Stock Value</p>
                                    <h4 class="fs-22 fw-semibold mb-0">${{ number_format($stockValueByLocation->sum('total_value'), 2) }}</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info-subtle rounded-circle fs-3">
                                        <i class="bi bi-cash-stack text-info"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STOCK VALUE BY LOCATION -->
            <div class="row mt-4">
                <div class="col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Stock Value by Location</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Location</th>
                                            <th>Total Value</th>
                                            <th>Number of Products</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalValue = $stockValueByLocation->sum('total_value');
                                        @endphp
                                        @foreach($stockValueByLocation as $location)
                                            @if($location->total_value > 0)
                                                @php
                                                    $percentage = $totalValue > 0 ? ($location->total_value / $totalValue * 100) : 0;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-shop me-2"></i>
                                                            <span>{{ $location->name }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="fw-semibold">${{ number_format($location->total_value, 2) }}</td>
                                                    <td>{{ $location->total_products ?? 0 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-grow-1 me-3">
                                                                <div class="progress progress-sm" style="height: 5px;">
                                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percentage }}%"></div>
                                                                </div>
                                                            </div>
                                                            <div class="flex-shrink-0">
                                                                <span>{{ number_format($percentage, 1) }}%</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Low Stock Alert</h5>
                        </div>
                        <div class="card-body">
                            @if($lowStockProducts->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-end">Stock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($lowStockProducts as $product)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if($product->thumbnail)
                                                                <img src="{{ asset('storage/' . $product->thumbnail) }}" class="rounded me-2" width="30" height="30" alt="{{ $product->title }}">
                                                            @endif
                                                            <div>
                                                                <div class="fw-semibold">{{ Str::limit($product->title, 20) }}</div>
                                                                <small class="text-muted">{{ $product->sku }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                            {{ $product->current_stock ?? 0 }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-2">
                                    <a href="{{ route('inventory.stock-levels') }}?stock_status=low_stock" class="btn btn-outline-warning btn-sm">
                                        View All Low Stock
                                    </a>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="bi bi-check-circle fs-1 text-success"></i>
                                    <p class="mt-2">All products have sufficient stock</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT TRANSACTIONS -->
            <div class="row mt-4">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Recent Transactions</h5>
                            <a href="{{ route('inventory.index') }}" class="btn btn-outline-primary btn-sm">
                                View All
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-centered align-middle table-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Product</th>
                                            <th>Location</th>
                                            <th>Quantity</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentTransactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                                                <td>
                                                    @php
                                                        $typeColors = [
                                                            'in' => 'success',
                                                            'out' => 'danger',
                                                            'adjustment' => 'warning',
                                                            'transfer' => 'info'
                                                        ];
                                                    @endphp
                                                    <span class="badge bg-{{ $typeColors[$transaction->type] ?? 'secondary' }}-subtle text-{{ $typeColors[$transaction->type] ?? 'secondary' }} border border-{{ $typeColors[$transaction->type] ?? 'secondary' }}-subtle">
                                                        {{ ucfirst($transaction->type) }}
                                                    </span>
                                                </td>
                                                <td>{{ $transaction->product->title }}</td>
                                                <td>{{ $transaction->stockLocation->name }}</td>
                                                <td class="{{ $transaction->type === 'in' ? 'text-success' : 'text-danger' }}">
                                                    {{ $transaction->type === 'in' ? '+' : '-' }}{{ $transaction->quantity }}
                                                </td>
                                                <td>{{ $transaction->reference_number }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    No recent transactions
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// You can add charts or additional JavaScript here if needed
</script>
@endsection