@extends('store.layout')

@section('title', 'Addresses | ' . ($storeSettings['name'] ?? 'GozakMart'))
@push('meta')<meta name="robots" content="noindex">@endpush

@section('content')
<div class="wrap account">
    @include('store.account._nav')
    <section class="box">
        <div class="sec-head">
            <h2>Addresses ({{ $addresses->count() }})</h2>
            <a href="{{ route('store.account.addresses.create') }}">+ Add address</a>
        </div>
        @if($addresses->isEmpty())
            <div class="empty">
                <div class="big">📍</div>
                <h2>No addresses yet</h2>
                <p class="muted">Add where you'd like your orders delivered.</p>
                <a href="{{ route('store.account.addresses.create') }}" class="btn btn-primary">Add an address</a>
            </div>
        @else
            <div class="addr-grid">
                @foreach($addresses as $a)
                    <div class="addr-card">
                        <strong>{{ $a->name }}</strong> @if($a->is_default)<span class="tag">Default</span>@endif
                        <div style="margin:6px 0">{{ $a->street }}<br>{{ $a->lga ? $a->lga . ', ' : '' }}{{ $a->city }}, {{ $a->state }}</div>
                        <div class="muted">{{ $a->phone_number }}</div>
                        @if(!$a->latitude)<div style="color:var(--brand);font-size:12px;margin-top:4px">No map pin yet</div>@endif
                        <div style="display:flex;gap:8px;margin-top:10px">
                            <a href="{{ route('store.account.addresses.edit', $a->id) }}" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post" action="{{ route('store.account.addresses.destroy', $a->id) }}" onsubmit="return confirm('Remove this address?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm">Remove</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
