<?php

namespace App\Http\Controllers\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DeliveryRider;
use App\Models\Dispatch\DeliveryZone;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiderZoneController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage riders|Manage dispatch|View dispatch', ['only' => ['riders', 'zones']]);
        $this->middleware('permission:Manage riders', ['except' => ['riders', 'zones']]);
    }

    // ── Riders ───────────────────────────────────────────────────────────────

    public function riders()
    {
        $riders = DeliveryRider::with('branch')
            ->withCount(['batches as runs_count' => fn ($q) => $q->where('status', 'completed')])
            ->orderByDesc('is_active')->orderBy('name')->get();
        $branches  = StockLocation::active()->orderBy('name')->get(['id', 'name']);
        $pagetitle = 'Delivery riders';
        return view('dispatch.riders', compact('riders', 'branches', 'pagetitle'));
    }

    public function storeRider(Request $request)
    {
        DeliveryRider::create($this->riderData($request) + ['is_active' => true]);
        return back()->with('success', 'Rider added.');
    }

    public function updateRider(Request $request, DeliveryRider $rider)
    {
        $rider->update($this->riderData($request) + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Rider updated.');
    }

    private function riderData(Request $request): array
    {
        return $request->validate([
            'name'              => 'required|string|max:120',
            'phone'             => 'nullable|string|max:32',
            'vehicle_type'      => ['required', Rule::in(array_keys(DeliveryRider::VEHICLES))],
            'vehicle_plate'     => 'nullable|string|max:32',
            'capacity'          => 'nullable|integer|min:1|max:999',
            'stock_location_id' => 'nullable|integer|exists:stock_locations,id',
            'notes'             => 'nullable|string|max:500',
        ]);
    }

    // ── Zones ────────────────────────────────────────────────────────────────

    public function zones()
    {
        $zones     = DeliveryZone::with('branch')->orderByDesc('is_active')->orderBy('sort_order')->orderBy('name')->get();
        $branches  = StockLocation::active()->orderBy('name')->get(['id', 'name']);
        $pagetitle = 'Delivery zones';
        return view('dispatch.zones', compact('zones', 'branches', 'pagetitle'));
    }

    public function storeZone(Request $request)
    {
        DeliveryZone::create($this->zoneData($request) + ['is_active' => true]);
        return back()->with('success', 'Zone added.');
    }

    public function updateZone(Request $request, DeliveryZone $zone)
    {
        $zone->update($this->zoneData($request, $zone) + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Zone updated.');
    }

    private function zoneData(Request $request, ?DeliveryZone $zone = null): array
    {
        $request->merge(['code' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->code))]);
        $data = $request->validate([
            'code'              => ['required', 'string', 'min:2', 'max:8',
                Rule::unique('delivery_zones', 'code')->where('stock_location_id', $request->stock_location_id)->ignore($zone?->id)],
            'name'              => 'required|string|max:120',
            'areas'             => 'nullable|string|max:5000',
            'color'             => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order'        => 'nullable|integer|min:0|max:999',
            'stock_location_id' => 'nullable|integer|exists:stock_locations,id',
        ]);
        // One area per line (or comma separated), in the order a rider would pass them.
        $data['areas'] = collect(preg_split('/[\r\n,]+/', (string) ($data['areas'] ?? '')))
            ->map(fn ($a) => trim($a))->filter()->unique(fn ($a) => mb_strtolower($a))->values()->all();
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        return $data;
    }
}
