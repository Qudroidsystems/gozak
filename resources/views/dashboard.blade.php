@extends('layouts.master')

@section('title', 'Dashboard')

@section('css')
    <!-- ApexCharts CSS -->
    <link href="{{ asset('assets/libs/apexcharts/apexcharts.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-4">
                    <div class="card card-height-100 border-0 overflow-hidden">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-md-6">
                                    <div class="card shadow-none border-end-md border-bottom rounded-0 mb-0">
                                        <div class="card-body">
                                            <div class="dropdown float-end">
                                                <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="text-muted fs-lg"><i class="mdi mdi-dots-vertical align-middle"></i></span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="#">Today</a>
                                                    <a class="dropdown-item" href="#">Last Week</a>
                                                    <a class="dropdown-item" href="#">Last Month</a>
                                                    <a class="dropdown-item" href="#">Current Year</a>
                                                </div>
                                            </div>
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                                    <i class="ph-wallet"></i>
                                                </span>
                                            </div>
                                            <div class="mt-4">
                                                <p class="text-uppercase fw-medium text-muted text-truncate fs-sm">Total Revenue</p>
                                                <h4 class="fw-semibold mb-3">${{ number_format($totalRevenue / 1000000, 2) }}M</h4>
                                                <div class="d-flex align-items-center gap-2">
                                                    <h5 class="text-success fs-xs mb-0">
                                                        <i class="ri-arrow-right-up-line fs-sm align-middle"></i> +19.07%
                                                    </h5>
                                                    <p class="text-muted mb-0">than last week</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-none border-bottom rounded-0 mb-0">
                                        <div class="card-body">
                                            <div class="dropdown float-end">
                                                <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="text-muted fs-lg"><i class="mdi mdi-dots-vertical align-middle"></i></span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="#">Today</a>
                                                    <a class="dropdown-item" href="#">Last Week</a>
                                                    <a class="dropdown-item" href="#">Last Month</a>
                                                    <a class="dropdown-item" href="#">Current Year</a>
                                                </div>
                                            </div>
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-dark-subtle text-dark rounded-circle fs-3">
                                                    <i class="ph-bag"></i>
                                                </span>
                                            </div>
                                            <div class="mt-4">
                                                <p class="text-uppercase fw-medium text-muted text-truncate fs-sm">Orders</p>
                                                <h4 class="fw-semibold mb-3">{{ $totalOrders }}</h4>
                                                <div class="d-flex align-items-center gap-2">
                                                    <h5 class="text-success fs-xs mb-0">
                                                        <i class="ri-arrow-right-up-line fs-sm align-middle"></i> +8.13%
                                                    </h5>
                                                    <p class="text-muted mb-0">than last week</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-none border-end-md rounded-0 mb-0">
                                        <div class="card-body">
                                            <div class="dropdown float-end">
                                                <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="text-muted fs-lg"><i class="mdi mdi-dots-vertical align-middle"></i></span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="#">Today</a>
                                                    <a class="dropdown-item" href="#">Last Week</a>
                                                    <a class="dropdown-item" href="#">Last Month</a>
                                                    <a class="dropdown-item" href="#">Current Year</a>
                                                </div>
                                            </div>
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-light text-body rounded-circle fs-3">
                                                    <i class="ph-eye"></i>
                                                </span>
                                            </div>
                                            <div class="mt-4">
                                                <p class="text-uppercase fw-medium text-muted text-truncate fs-sm">Products</p>
                                                <h4 class="fw-semibold mb-3">{{ $totalProducts }}</h4>
                                                <div class="d-flex align-items-center gap-2">
                                                    <h5 class="text-danger fs-xs mb-0">
                                                        <i class="ri-arrow-right-down-line fs-sm align-middle"></i> -2.01%
                                                    </h5>
                                                    <p class="text-muted mb-0">than last week</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-none border-top border-top-md-0 rounded-0 mb-0">
                                        <div class="card-body">
                                            <div class="dropdown float-end">
                                                <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="text-muted fs-lg"><i class="mdi mdi-dots-vertical align-middle"></i></span>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="#">Today</a>
                                                    <a class="dropdown-item" href="#">Last Week</a>
                                                    <a class="dropdown-item" href="#">Last Month</a>
                                                    <a class="dropdown-item" href="#">Current Year</a>
                                                </div>
                                            </div>
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                                    <i class="ph-users-three"></i>
                                                </span>
                                            </div>
                                            <div class="mt-4">
                                                <p class="text-uppercase fw-medium text-muted text-truncate fs-sm">Customers</p>
                                                <h4 class="fw-semibold mb-3">{{ number_format($totalCustomers / 1000, 1) }}k</h4>
                                                <div class="d-flex align-items-center gap-2">
                                                    <h5 class="text-success fs-xs mb-0">
                                                        <i class="ri-arrow-right-up-line fs-sm align-middle"></i> +10.42%
                                                    </h5>
                                                    <p class="text-muted mb-0">than last week</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-8">
                    <div class="card">
                        <div class="row g-0">
                            <div class="col-xl-9">
                                <div class="card-header border-0 align-items-center d-flex">
                                    <h4 class="card-title mb-0 flex-grow-1">Revenue</h4>
                                    <div>
                                        <button type="button" class="btn btn-subtle-secondary btn-sm">ALL</button>
                                        <button type="button" class="btn btn-subtle-secondary btn-sm">1M</button>
                                        <button type="button" class="btn btn-subtle-secondary btn-sm">6M</button>
                                        <button type="button" class="btn btn-subtle-primary btn-sm">1Y</button>
                                    </div>
                                </div>
                                <div class="card-body ps-0">
                                    <div class="w-100">
                                        <div id="market-overview" data-colors='["--tb-primary", "--tb-secondary"]' class="apex-charts" dir="ltr"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3">
                                <div class="card-body border-start-xl border-top border-top-xl-0 border-2 h-100">
                                    <div>
                                        <p class="text-muted mb-2">Budget (USD)</p>
                                        <h4>${{ number_format($totalRevenue, 2) }} <small class="text-success fs-sm fw-normal"><i class="ph-arrow-up align-baseline"></i> 2.17%</small></h4>
                                        <p class="text-muted">Budget than last years</p>
                                        <div class="mx-3">
                                            <div id="mini-chart-6" data-colors='["--tb-primary"]' class="apex-charts" dir="ltr"></div>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-muted mb-2">Avg Rating</p>
                                        <h4>{{ number_format($avgRating, 1) }}/5 <small class="text-danger fs-sm fw-normal"><i class="ph-arrow-down align-baseline"></i> -1.36%</small></h4>
                                        <p class="text-muted">Avg than last years</p>
                                        <div class="mx-3">
                                            <div id="mini-chart-7" data-colors='["--tb-info"]' class="apex-charts" dir="ltr"></div>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#addAmount">Add Amount</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Sales by Countries</h4>
                            <div class="flex-shrink-0">
                                <button type="button" class="btn btn-subtle-primary btn-sm">Export Report</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div id="world-map-line-markers" data-colors='["--tb-light"]' style="height: 340px"></div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="mb-4">
                                        <h6 class="text-muted mb-3 fw-medium fs-xs text-uppercase">Compared to last month</h6>
                                        <h3><span class="counter-value" data-target="{{ array_sum(array_column($salesByCountries, 'sales')) }}"></span> <small class="text-muted fw-normal fs-sm">Sales</small></h3>
                                    </div>
                                    <div>
                                        <ul class="list-unstyled vstack gap-2">
                                            @foreach($salesByCountries as $country)
                                            <li class="p-2 rounded">
                                                <i class="ri-checkbox-blank-circle-fill text-primary align-bottom me-1"></i> {{ $country['country'] }} <span class="float-end">{{ $country['sales'] }}</span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="card card-height-100">
                        <div class="card-header d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Traffic Source</h4>
                            <div class="dropdown card-header-dropdown float-end">
                                <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="ph-dots-three-outline-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="#">Today</a>
                                    <a class="dropdown-item" href="#">Last Week</a>
                                    <a class="dropdown-item" href="#">Last Month</a>
                                    <a class="dropdown-item" href="#">Current Year</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="column_chart" data-colors='["--tb-primary", "--tb-light"]' class="apex-charts" dir="ltr"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6">
                    <div class="card card-height-100">
                        <div class="card-header d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Recent Sales</h4>
                            <a href="#!" class="text-muted">View All <i class="ph-caret-right align-middle"></i></a>
                        </div>
                        <div class="card-body px-0">
                            <div data-simplebar class="px-3" style="max-height: 360px;">
                                <table class="table mb-0">
                                    <tbody>
                                        @foreach($recentSales as $sale)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <div class="flex-shrink-0">
                                                        <img src="{{ asset('assets/images/users/48/avatar-2.jpg') }}" alt="" class="avatar-sm rounded-circle p-1">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="fs-md mb-1">{{ $sale['user_name'] }}</h6>
                                                        <p class="text-muted mb-0">{{ $sale['date'] }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <h6 class="fs-md">${{ $sale['amount'] }}</h6>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card" id="contactList">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Latest Orders</h4>
                            <div class="flex-shrink-0">
                                <div class="dropdown card-header-dropdown sortble-dropdown">
                                    <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="fw-semibold text-uppercase fs-12">Sort by:</span><span class="text-muted dropdown-title">Order Date</span> <i class="mdi mdi-chevron-down ms-1"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button class="dropdown-item sort" data-sort="order_date">Order Date</button>
                                        <button class="dropdown-item sort" data-sort="order_id">Order ID</button>
                                        <button class="dropdown-item sort" data-sort="amount">Amount</button>
                                        <button class="dropdown-item sort" data-sort="status">Status</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-borderless table-centered align-middle table-nowrap mb-0">
                                    <thead class="text-muted table-light">
                                        <tr>
                                            <th scope="col" class="sort cursor-pointer" data-sort="order_date">Order Date</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="order_id">Order ID</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="shop">Shop</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="customer">Customers</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="products">Products</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="amount">Amount</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="status">Status</th>
                                            <th scope="col" class="sort cursor-pointer" data-sort="rating">Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list">
                                        @foreach($latestOrders as $order)
                                        <tr>
                                            <td class="order_date">{{ $order['order_date'] }}</td>
                                            <td class="order_id">
                                                <a href="#" class="fw-medium link-primary">#{{ $order['order_id'] }}</a>
                                            </td>
                                            <td class="shop">
                                                <img src="{{ asset('assets/images/companies/img-1.png') }}" alt="" class="avatar-xxs rounded-circle">
                                            </td>
                                            <td class="customer">{{ $order['customer'] }}</td>
                                            <td class="products">{{ $order['products'] }}</td>
                                            <td class="amount"><span class="fw-medium">${{ $order['amount'] }}</span></td>
                                            <td class="status">
                                                <span class="badge bg-{{ $order['status'] == 'delivered' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'secondary') }}-subtle text-{{ $order['status'] == 'delivered' ? 'success' : ($order['status'] == 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($order['status']) }}</span>
                                            </td>
                                            <td class="rating">
                                                <h5 class="fs-md fw-medium mb-0">{{ $order['rating'] != '-' ? $order['rating'] : '-' }}</h5>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h4 class="card-title mb-0 flex-grow-1">Popular Products</h4>
                            <div class="flex-shrink-0">
                                <div class="dropdown card-header-dropdown">
                                    <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="fw-semibold text-uppercase">Sort by:</span><span class="text-muted">Today<i class="mdi mdi-chevron-down ms-1"></i></span>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="#">Today</a>
                                        <a class="dropdown-item" href="#">Yesterday</a>
                                        <a class="dropdown-item" href="#">Last 7 Days</a>
                                        <a class="dropdown-item" href="#">Last 30 Days</a>
                                        <a class="dropdown-item" href="#">This Month</a>
                                        <a class="dropdown-item" href="#">Last Month</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body px-0">
                            <div data-simplebar class="px-3" style="max-height: 333px;">
                                <div class="vstack gap-2">
                                    @foreach($popularProducts as $product)
                                    <div class="p-2 border border-dashed">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-sm flex-shrink-0">
                                                <div class="avatar-title bg-light rounded">
                                                    <img src="{{ $product['image'] }}" alt="" class="avatar-xs">
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#">
                                                    <h6 class="fs-md mb-2">{{ $product['title'] }}</h6>
                                                </a>
                                                <ul class="hstack list-unstyled gap-2 mb-0 fs-sm fw-medium text-muted">
                                                    <li><i class="ph-star align-baseline"></i> {{ $product['rating'] }}</li>
                                                    <li><i class="ph-shopping-cart align-baseline"></i> {{ $product['sales'] }}</li>
                                                </ul>
                                            </div>
                                            <div class="text-end">
                                                <h5 class="fs-md text-primary mb-0">${{ $product['price'] }}</h5>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <button class="btn btn-secondary btn-icon btn-sm" data-bs-toggle="modal" data-bs-target="#productModal"><i class="ph-arrow-right"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">Orders Status</h5>
                            <div class="dropdown card-header-dropdown">
                                <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="ph-dots-three-outline-vertical"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="#">Current Years</a>
                                    <a class="dropdown-item" href="#">Last Years</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 pb-1 text-center">
                                <h6 class="mb-0">01 Jan, {{ date('Y') }} - {{ date('d M, Y') }}</h6>
                            </div>
                            @foreach($orderStatuses as $status => $count)
                            <div class="row align-items-center mb-3">
                                <div class="col-lg-4">
                                    <div class="hstack gap-2">
                                        <p class="mb-0 flex-grow-1">{{ ucfirst($status) }}</p>
                                        <h6 class="mb-0">{{ $count }}</h6>
                                    </div>
                                </div>
                                <div class="col-lg-8">
                                    <div class="progress animated-progress" role="progressbar" aria-valuenow="{{ ($count / array_sum($orderStatuses)) * 100 }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-{{ $status == 'delivered' ? 'success' : ($status == 'pending' ? 'warning' : 'primary') }}" style="width: {{ ($count / array_sum($orderStatuses)) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6">
                    <div class="card card-height-100">
                        <div class="card-header d-flex">
                            <h5 class="card-title flex-grow-1 mb-0">Recent Activity</h5>
                            <div class="flex-shrink-0">
                                <a href="#" class="btn btn-subtle-info btn-sm">View More <i class="ph-caret-right align-middle"></i></a>
                            </div>
                        </div>
                        <div class="card-body px-0">
                            <div data-simplebar class="px-3" style="max-height: 258px;">
                                <div class="acitivity-timeline acitivity-main">
                                    @foreach($recentActivity as $activity)
                                    <div class="acitivity-item d-flex">
                                        <div class="flex-shrink-0 avatar-xs acitivity-avatar">
                                            <div class="avatar-title bg-success-subtle text-success rounded-circle">
                                                <i class="{{ $activity['icon'] }}"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1 lh-base">{{ $activity['title'] }}</h6>
                                            <p class="text-muted mb-2">{{ $activity['description'] }}</p>
                                            <small class="mb-0 text-muted">{{ $activity['time'] }}</small>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6">
                    <div class="card card-height-100">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="card-title flex-grow-1 mb-0">Insight</h5>
                            <div class="flex-shrink-0">
                                <div class="dropdown card-header-dropdown">
                                    <a class="text-reset dropdown-btn" href="#" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="text-muted">This Month<i class="mdi mdi-chevron-down ms-1"></i></span>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="#">This Month</a>
                                        <a class="dropdown-item" href="#">Last Month</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="vstack gap-2">
                                @foreach($insights as $insight)
                                <div class="border py-2 px-3 w-100 rounded d-flex align-items-center gap-2">
                                    <i class="bi bi-check2-square text-primary"></i>
                                    <h6 class="mb-0">{{ $insight }}</h6>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>document.write(new Date().getFullYear())</script> © Ecommerce.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
                        Design & Develop by Your Team
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>
@endsection

@section('script')
    <!-- ApexCharts JS -->
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/dashboard-ecommerce.init.js') }}"></script>

    <script>
        // Revenue Chart Data
        var revenueChartData = @json($revenueData);
        var revenueLabels = revenueChartData.map(item => item.month);
        var revenueSeries = [{ name: 'Revenue', data: revenueChartData.map(item => item.revenue) }];

        // Initialize charts (assuming dashboard-ecommerce.init.js handles initialization)
        // You may need to customize the JS file to use these dynamic data
    </script>
@endsection