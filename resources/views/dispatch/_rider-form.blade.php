<form method="POST" action="{{ $rider ? route('dispatch.riders.update', $rider) : route('dispatch.riders.store') }}">
    @csrf
    @if($rider) @method('PUT') @endif
    <div class="row g-2">
        <div class="{{ $rider ? 'col-md-4' : 'col-12' }}">
            <label class="form-label small mb-1">Name</label>
            <input name="name" class="form-control form-control-sm" required maxlength="120" value="{{ $rider?->name }}">
        </div>
        <div class="{{ $rider ? 'col-md-4' : 'col-12' }}">
            <label class="form-label small mb-1">Phone</label>
            <input name="phone" class="form-control form-control-sm" maxlength="32" value="{{ $rider?->phone }}">
        </div>
        <div class="{{ $rider ? 'col-md-4' : 'col-6' }}">
            <label class="form-label small mb-1">Vehicle</label>
            <select name="vehicle_type" class="form-select form-select-sm">
                @foreach(\App\Models\Dispatch\DeliveryRider::VEHICLES as $k => $l)<option value="{{ $k }}" @selected(($rider?->vehicle_type ?? 'motorbike') === $k)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div class="{{ $rider ? 'col-md-3' : 'col-6' }}">
            <label class="form-label small mb-1">Plate number</label>
            <input name="vehicle_plate" class="form-control form-control-sm" maxlength="32" value="{{ $rider?->vehicle_plate }}">
        </div>
        <div class="{{ $rider ? 'col-md-3' : 'col-6' }}">
            <label class="form-label small mb-1">Max parcels per run</label>
            <input type="number" min="1" max="999" name="capacity" class="form-control form-control-sm" value="{{ $rider?->capacity }}">
        </div>
        <div class="{{ $rider ? 'col-md-3' : 'col-6' }}">
            <label class="form-label small mb-1">Home branch</label>
            <select name="stock_location_id" class="form-select form-select-sm">
                <option value="">Any branch</option>
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected($rider?->stock_location_id == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </div>
        <div class="{{ $rider ? 'col-md-3' : 'col-12' }}">
            <label class="form-label small mb-1">Notes</label>
            <input name="notes" class="form-control form-control-sm" maxlength="500" value="{{ $rider?->notes }}">
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2">
        @if($rider)
            <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="ra{{ $rider->id }}" @checked($rider->is_active)>
                <label class="form-check-label small" for="ra{{ $rider->id }}">Active</label>
            </div>
        @else <span></span> @endif
        <button class="btn btn-sm btn-primary">{{ $rider ? 'Save' : 'Add rider' }}</button>
    </div>
</form>
