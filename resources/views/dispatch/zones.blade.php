@extends('layouts.master')

@section('title', 'Delivery zones')

@section('content')
@php $canEdit = auth()->user()->can('Manage riders'); @endphp
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('dispatch._nav', ['title' => 'Delivery zones', 'subtitle' => 'Areas each branch delivers to. Orders are matched to a zone from their LGA, town or street, and the zone code starts every route code (IKJ-01, LKI-02…).', 'back' => route('dispatch.index'), 'backLabel' => 'Dispatch board'])

    <div class="alert alert-info small">
        <i class="ri-lightbulb-line"></i> List a zone's areas <b>in the order a rider drives past them</b>. When a batch is created, stops are sorted in that order — so the manifest and the stop numbers on the labels follow the road.
    </div>

    <div class="row g-3">
        <div class="col-xl-{{ $canEdit ? 8 : 12 }}">
            <x-cb.card title="Zones" icon="ri-map-pin-range-line" :count="$zones->count()" :flush="true">
                <div class="table-responsive">
                    <table class="cb-table">
                        <thead><tr><th>Code</th><th>Zone</th><th>Branch</th><th>Areas (route order)</th>@if($canEdit)<th></th>@endif</tr></thead>
                        <tbody>
                        @forelse($zones as $z)
                            <tr class="{{ $z->is_active ? '' : 'opacity-50' }}">
                                <td><span class="badge" style="background: {{ $z->color ?: '#334155' }}; font-size: 13px;">{{ $z->code }}</span></td>
                                <td class="fw-semibold">{{ $z->name }}@unless($z->is_active) <span class="badge bg-secondary">inactive</span>@endunless</td>
                                <td>{{ $z->branch?->name ?? 'All branches' }}</td>
                                <td class="small">{{ \Illuminate\Support\Str::limit($z->areasText(), 140) ?: '—' }}</td>
                                @if($canEdit)
                                    <td class="text-end"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#zone{{ $z->id }}"><i class="ri-pencil-line"></i></button></td>
                                @endif
                            </tr>
                            @if($canEdit)
                                <tr class="collapse" id="zone{{ $z->id }}"><td colspan="5" style="background: var(--cb-surface-2);">
                                    @include('dispatch._zone-form', ['zone' => $z])
                                </td></tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No zones yet. Add your first one — e.g. code <b>IKJ</b>, name <b>Ikeja</b>, areas <i>Allen, Opebi, Alausa, Oregun</i>.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-cb.card>
        </div>
        @if($canEdit)
            <div class="col-xl-4">
                <x-cb.card title="Add a zone" icon="ri-add-line">
                    @include('dispatch._zone-form', ['zone' => null])
                </x-cb.card>
            </div>
        @endif
    </div>

</div>
</div>
</div>
@endsection
