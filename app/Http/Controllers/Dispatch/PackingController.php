<?php

namespace App\Http\Controllers\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\OrderParcel;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Services\Dispatch\DispatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Order-level packing: parcels, picked quantities, substitutions, packer/checker
 * sign-off, parcel labels and the packing slip.
 */
class PackingController extends Controller
{
    /** 100 × 150 mm (4 × 6 in) thermal label, and a compact 100 × 75 mm one, in points. */
    public const LABEL_SIZES = [
        'standard' => [0, 0, 283.46, 425.20],
        'compact'  => [0, 0, 283.46, 212.60],
    ];

    public function __construct(private DispatchService $dispatch)
    {
        $this->middleware('permission:Pack orders|Manage order', ['only' => ['save', 'verify']]);
        $this->middleware('permission:Pack orders|View order|Manage order|View dispatch', ['only' => ['labels', 'packingSlip']]);
    }

    public function save(Request $request, string $order)
    {
        $order = Order::with('items')->findOrFail($order);

        $data = $request->validate([
            'stock_location_id'          => 'nullable|integer|exists:stock_locations,id',
            'delivery_zone_id'           => 'nullable|integer|exists:delivery_zones,id',
            'packing_notes'              => 'nullable|string|max:1000',
            'items'                      => 'nullable|array',
            'items.*.picked_quantity'    => 'nullable|integer|min:0|max:100000',
            'items.*.substitution'       => 'nullable|string|max:500',
            'parcels'                    => 'required|array|min:1|max:99',
            'parcels.*.package_type'     => 'required|string|in:' . implode(',', array_keys(OrderParcel::TYPES)),
            'parcels.*.handling'         => 'nullable|array',
            'parcels.*.handling.*'       => 'string|in:' . implode(',', array_keys(OrderParcel::HANDLING)),
            'parcels.*.weight_kg'        => 'nullable|numeric|min:0|max:1000',
            'parcels.*.contents'         => 'nullable|string|max:500',
        ]);

        try {
            $this->dispatch->savePacking($order, $data, (int) auth()->id());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Packing saved. Print the labels, then ask a colleague to check and verify.');
    }

    public function verify(Request $request, string $order)
    {
        $order = Order::findOrFail($order);
        try {
            $this->dispatch->verifyPacking($order, (int) auth()->id(), $request->boolean('self_check'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Packing verified — the order is ready for dispatch.');
    }

    /** Parcel labels for one order (all parcels, or ?parcel=<id> for one). */
    public function labels(Request $request, string $order)
    {
        $order = Order::with(['parcels', 'shippingAddress', 'billingAddress', 'user', 'customer', 'deliveryZone', 'dispatchBatch', 'branch'])
            ->findOrFail($order);

        $parcels = $order->parcels;
        if ($request->filled('parcel')) {
            $parcels = $parcels->where('id', (int) $request->parcel)->values();
        }
        if ($parcels->isEmpty()) {
            return back()->with('error', 'Record the parcels for this order before printing labels.');
        }

        $this->dispatch->markLabelsPrinted($order);

        return $this->renderLabels($parcels->map(fn ($p) => $p->setRelation('order', $order)), $request->get('size'), "labels-{$order->ref}.pdf");
    }

    public function packingSlip(string $order)
    {
        $order = Order::with(['items.product', 'parcels', 'shippingAddress', 'billingAddress', 'user', 'customer', 'packer', 'checker', 'branch', 'deliveryZone', 'dispatchBatch'])
            ->findOrFail($order);
        $store = $this->store();

        $pdf = Pdf::loadView('dispatch.pdf.packing-slip', compact('order', 'store'))->setPaper('a4', 'portrait')->setOption('isFontSubsettingEnabled', true);
        return $pdf->stream('packing-slip-' . $order->ref . '.pdf');
    }

    /** Shared by order labels and batch labels. */
    public function renderLabels($parcels, ?string $size, string $filename)
    {
        $size    = array_key_exists((string) $size, self::LABEL_SIZES) ? $size : 'standard';
        $store   = $this->store();
        $compact = $size === 'compact';

        $pdf = Pdf::loadView('dispatch.pdf.labels', compact('parcels', 'store', 'compact'))
            ->setPaper(self::LABEL_SIZES[$size])->setOption('isFontSubsettingEnabled', true);
        return $pdf->stream($filename);
    }

    private function store(): array
    {
        $s = null;
        try {
            $s = StoreSetting::query()->first();
        } catch (\Throwable $e) {
        }
        return [
            'name'  => $s->store_name ?? $s->name ?? config('app.name', 'GozakMart'),
            'phone' => $s->phone ?? $s->store_phone ?? null,
        ];
    }
}
