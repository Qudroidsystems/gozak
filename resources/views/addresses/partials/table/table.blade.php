<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Address</th>
                <th>Phone</th>
                <th>Default</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($addresses as $address)
            <tr>
                <td>
                    <div class="fw-semibold">{{ $address->user->first_name }} {{ $address->user->last_name }}</div>
                    <small class="text-muted">{{ $address->user->email }}</small>
                </td>
                <td>
                    @if($address->name)<div class="fw-bold text-primary mb-1">{{ $address->name }}</div>@endif
                    {{ $address->street }}<br>
                    <small>{{ $address->city }}, {{ $address->state }} {{ $address->postal_code }}</small><br>
                    <small class="text-muted">{{ $address->country }}</small>
                </td>
                <td>{{ $address->phone_number }}</td>
                <td>
                    @if($address->is_default)
                        <span class="badge badge-default">Default</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-light border" onclick="viewAddress({{ $address->id }})" title="View">
                        <i class="bi bi-eye-fill text-primary"></i>
                    </button>
                    <button class="btn btn-sm btn-light border" onclick="editAddress({{ $address->id }})" title="Edit">
                        <i class="bi bi-pencil-fill text-warning"></i>
                    </button>
                    <button class="btn btn-sm btn-light border" onclick="deleteAddress({{ $address->id }})" title="Delete">
                        <i class="bi bi-trash-fill text-danger"></i>
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-5 text-muted">No addresses found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($addresses->hasPages())
<div class="mt-4">
    {!! $addresses->appends(request()->query())->links('pagination::bootstrap-5') !!}
</div>
@endif
