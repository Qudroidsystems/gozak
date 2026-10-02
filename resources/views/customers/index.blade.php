@extends('layouts.master')
@section('title', 'Customer Management')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Customers" icon="ri-user-heart-line" subtitle="Everyone who has signed up on the GozakMart app, with their order count and lifetime spend.">
        <x-slot:actions>
            <a href="{{ route('customers.export') }}" class="cb-hero-btn"><i class="ri-file-excel-2-line"></i> Export Excel</a>
        </x-slot:actions>
    </x-cb.hero>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Total customers" :value="number_format($stats['total'])" icon="ri-group-line" accent="sky" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Verified" :value="number_format($stats['active'])" icon="ri-shield-check-line" accent="green" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="New this month" :value="number_format($stats['new_month'])" icon="ri-user-add-line" accent="violet" /></div>
        <div class="col-xl-3 col-md-6"><x-cb.stat label="Lifetime revenue" :value="\App\Support\Money::fmt($stats['total_spent'])" icon="ri-money-dollar-circle-line" accent="amber" /></div>
    </div>

    <x-cb.card title="All Customers" icon="ri-team-line" :flush="true">
        <x-slot:tools>
            <select id="f-status" class="form-select form-select-sm" data-dt-filter="#customersTable" style="width:auto;">
                <option value="">All customers</option>
                <option value="verified">Verified</option>
                <option value="unverified">Unverified</option>
                <option value="buyers">Have ordered</option>
            </select>
        </x-slot:tools>
        <div class="p-3 gz-dt-wrap">
            <table id="customersTable" class="table gz-dt align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-cb.card>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var table = GZ.dt('#customersTable', {
        url: @json(route('customers.data')),
        order: [[5, 'desc']],
        filters: function () { return { status: $('#f-status').val() }; },
        columns: [
            { data: 'customer',                name: 'customer' },
            { data: 'email',                   name: 'users.email' },
            { data: 'orders_count',            name: 'orders_count',            searchable: false },
            { data: 'orders_sum_total_amount', name: 'orders_sum_total_amount', searchable: false },
            { data: 'status',                  name: 'users.email_verified_at', searchable: false },
            { data: 'created_at',              name: 'users.created_at',        searchable: false }
        ]
    });
    // ?search=… (from the ⌘K spotlight) pre-fills the table search
    var s = new URLSearchParams(location.search).get('search');
    if (s) { table.search(s).draw(); }
});
</script>
@endsection
