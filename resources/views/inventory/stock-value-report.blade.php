@extends('layouts.master')

@section('title', 'Stock Value Report')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- PAGE TITLE -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle ?? 'Stock Value Report' }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                                <li class="breadcrumb-item active">Stock Value Report</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SUMMARY CARD -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card bg-primary-subtle border-0">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="card-title mb-1">Total Stock Value</h4>
                                    <h2 class="fw-bold mb-0">${{ number_format($totalValue, 2) }}</h2>
                                    <p class="text-muted mb-0">Across all locations</p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <i class="bi bi-currency-dollar display-1 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STOCK VALUE BY LOCATION -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Stock Value by Location</h5>
                </div>
                <div class="card-body">
                    @if(count($report) > 0)
                        <div class="table-responsive">
                            <table class="table table-centered align-middle table-nowrap mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Location</th>
                                        <th>Product Count</th>
                                        <th>Stock Value</th>
                                        <th>Percentage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report as $item)
                                        @php
                                            $percentage = $totalValue > 0 ? ($item['value'] / $totalValue) * 100 : 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $item['location']->name }}</div>
                                                @if($item['location']->is_default)
                                                    <small class="text-primary">(Default Location)</small>
                                                @endif
                                            </td>
                                            <td>{{ $item['product_count'] }}</td>
                                            <td>
                                                <span class="fw-bold">${{ number_format($item['value'], 2) }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <div class="progress" style="height: 5px;">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                 style="width: {{ $percentage }}%;" 
                                                                 aria-valuenow="{{ $percentage }}" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0 ms-2">
                                                        <span class="fw-medium">{{ number_format($percentage, 1) }}%</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="{{ route('inventory.index', ['location_id' => $item['location']->id]) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye me-1"></i> View Transactions
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>Total</th>
                                        <th>{{ array_sum(array_column($report, 'product_count')) }}</th>
                                        <th>${{ number_format($totalValue, 2) }}</th>
                                        <th>100%</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1"></i>
                            <p class="mt-2">No stock value data available</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- CHART SECTION (Optional) -->
            <div class="row mt-4">
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Value Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div id="valueChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top 5 Locations by Value</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                @foreach(array_slice($report, 0, 5) as $index => $item)
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">{{ $index + 1 }}. {{ $item['location']->name }}</h6>
                                            <small class="text-muted">{{ $item['product_count'] }} products</small>
                                        </div>
                                        <span class="badge bg-success rounded-pill">${{ number_format($item['value'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart if we have data
    @if(count($report) > 0)
    var chartData = @json($report);
    
    var labels = chartData.map(item => item.location.name);
    var values = chartData.map(item => item.value);
    
    var options = {
        series: values,
        chart: {
            type: 'donut',
            height: 300,
        },
        labels: labels,
        colors: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6610f2'],
        legend: {
            position: 'bottom'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Value',
                            formatter: function (w) {
                                return '${{ number_format($totalValue, 2) }}';
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return '$' + value.toLocaleString('en-US', {minimumFractionDigits: 2});
                }
            }
        }
    };
    
    var chart = new ApexCharts(document.querySelector("#valueChart"), options);
    chart.render();
    @endif
});
</script>
@endsection