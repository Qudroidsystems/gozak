{{-- Shared header for dispatch pages. --}}
<x-cb.hero :title="$title ?? 'Dispatch'" icon="ri-truck-line" :subtitle="$subtitle ?? 'Group packed orders into delivery runs, load parcels by scan, and hand them to the right rider.'" :back="$back ?? null" :back-label="$backLabel ?? 'Back'">
    <x-slot:actions>
        <button type="button" class="cb-hero-btn" onclick="gzParcelLookup()"><i class="ri-qr-scan-2-line"></i> Find a parcel</button>
        @canany(['Manage riders', 'Manage dispatch', 'View dispatch'])
            <a class="cb-hero-btn" href="{{ route('dispatch.riders') }}"><i class="ri-e-bike-2-line"></i> Riders</a>
            <a class="cb-hero-btn" href="{{ route('dispatch.zones') }}"><i class="ri-map-pin-range-line"></i> Zones</a>
        @endcanany
    </x-slot:actions>
</x-cb.hero>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="ri-checkbox-circle-line me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><i class="ri-error-warning-line me-1"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

@once
@push('scripts')
<script>
function gzParcelLookup() {
    Swal.fire({
        title: 'Find a parcel',
        input: 'text',
        inputPlaceholder: 'Scan or type the parcel code',
        showCancelButton: true,
        confirmButtonText: 'Look up',
        showLoaderOnConfirm: true,
        preConfirm: (code) => fetch(`{{ route('dispatch.lookup') }}?code=${encodeURIComponent(code || '')}`, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json()),
    }).then(res => {
        if (!res.isConfirmed) return;
        const d = res.value || {};
        if (!d.found) { Swal.fire('Not found', d.message || 'Unknown code', 'error'); return; }
        const esc = s => String(s ?? '—').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        Swal.fire({
            title: esc(d.code),
            html: `<div class="text-start small">
                <div><b>Order:</b> ${d.order_url ? `<a href="${d.order_url}">${esc(d.order)}</a>` : esc(d.order)} · parcel ${esc(d.parcel)}</div>
                <div><b>Area:</b> ${esc(d.area)}</div>
                <div><b>Status:</b> ${esc(d.status)}</div>
                <div><b>Batch:</b> ${d.batch_url ? `<a href="${d.batch_url}">${esc(d.batch)}</a>` : 'not on a batch'} ${d.route ? '· route <b>' + esc(d.route) + '</b>' : ''}</div>
                <div><b>Rider:</b> ${esc(d.rider)}</div></div>`,
            icon: 'info'
        });
    });
}
</script>
@endpush
@endonce
