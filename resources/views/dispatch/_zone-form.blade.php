<form method="POST" action="{{ $zone ? route('dispatch.zones.update', $zone) : route('dispatch.zones.store') }}">
    @csrf
    @if($zone) @method('PUT') @endif
    <div class="row g-2">
        <div class="col-4 col-md-{{ $zone ? 2 : 4 }}">
            <label class="form-label small mb-1">Code</label>
            <input name="code" class="form-control form-control-sm text-uppercase font-monospace" required minlength="2" maxlength="8" placeholder="IKJ" value="{{ $zone?->code }}">
        </div>
        <div class="col-8 col-md-{{ $zone ? 4 : 8 }}">
            <label class="form-label small mb-1">Name</label>
            <input name="name" class="form-control form-control-sm" required maxlength="120" placeholder="Ikeja" value="{{ $zone?->name }}">
        </div>
        <div class="col-md-{{ $zone ? 3 : 8 }}">
            <label class="form-label small mb-1">Branch</label>
            <select name="stock_location_id" class="form-select form-select-sm">
                <option value="">All branches</option>
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected($zone?->stock_location_id == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-6 col-md-{{ $zone ? 1 : 2 }}">
            <label class="form-label small mb-1">Colour</label>
            <input type="color" name="color" class="form-control form-control-sm form-control-color w-100" value="{{ $zone?->color ?: '#334155' }}">
        </div>
        <div class="col-6 col-md-{{ $zone ? 2 : 2 }}">
            <label class="form-label small mb-1">Sort</label>
            <input type="number" name="sort_order" min="0" max="999" class="form-control form-control-sm" value="{{ $zone?->sort_order ?? 0 }}">
        </div>
        <div class="col-12">
            <label class="form-label small mb-1">Areas — one per line, in driving order (LGA, town, estate or street names)</label>
            <textarea name="areas" rows="{{ $zone ? 3 : 5 }}" class="form-control form-control-sm" maxlength="5000" placeholder="Ikeja&#10;Allen&#10;Opebi&#10;Alausa">{{ $zone ? implode("\n", $zone->areas ?? []) : '' }}</textarea>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
        @if($zone)
            <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="za{{ $zone->id }}" @checked($zone->is_active)>
                <label class="form-check-label small" for="za{{ $zone->id }}">Active</label>
            </div>
        @else <span></span> @endif
        <button class="btn btn-sm btn-primary">{{ $zone ? 'Save' : 'Add zone' }}</button>
    </div>
</form>
