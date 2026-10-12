@extends('layouts.master')

@section('title', 'Delivery riders')

@section('content')
@php $canEdit = auth()->user()->can('Manage riders'); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('dispatch._nav', ['title' => 'Delivery riders', 'subtitle' => 'The people and vehicles that take parcels out. Their name, phone and plate print on every manifest.', 'back' => route('dispatch.index'), 'backLabel' => 'Dispatch board'])

    <div class="row g-3">
        <div class="col-xl-{{ $canEdit ? 8 : 12 }}">
            <x-cb.card title="Riders" icon="ri-e-bike-2-line" :count="$riders->count()" :flush="true">
                <div class="table-responsive">
                    <table class="cb-table">
                        <thead><tr><th>Rider</th><th>Vehicle</th><th>Branch</th><th class="num">Capacity</th><th class="num">Runs</th><th>Status</th>@if($canEdit)<th></th>@endif</tr></thead>
                        <tbody>
                        @forelse($riders as $r)
                            <tr class="{{ $r->is_active ? '' : 'opacity-50' }}">
                                <td><div class="fw-semibold">{{ $r->name }}</div><div class="small text-muted">{{ $r->phone }}</div></td>
                                <td>{{ $r->vehicleLabel() }}</td>
                                <td>{{ $r->branch?->name ?? 'Any' }}</td>
                                <td class="num">{{ $r->capacity ?: '—' }}</td>
                                <td class="num">{{ $r->runs_count }}</td>
                                <td>
                                    @if(!$r->is_active)<span class="badge bg-secondary">Inactive</span>
                                    @elseif($r->isOnRun())<span class="badge bg-primary">On a run</span>
                                    @else<span class="badge bg-success">Available</span>@endif
                                </td>
                                @if($canEdit)
                                    <td class="text-end"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#rider{{ $r->id }}"><i class="ri-pencil-line"></i></button></td>
                                @endif
                            </tr>
                            @if($canEdit)
                                <tr class="collapse" id="rider{{ $r->id }}"><td colspan="7" style="background: var(--cb-surface-2);">
                                    @include('dispatch._rider-form', ['rider' => $r])
                                </td></tr>
                            @endif
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No riders yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-cb.card>
        </div>
        @if($canEdit)
            <div class="col-xl-4">
                <x-cb.card title="Add a rider" icon="ri-user-add-line">
                    @include('dispatch._rider-form', ['rider' => null])
                </x-cb.card>
            </div>
        @endif
    </div>

</div>
</div>
</div>
@endsection
