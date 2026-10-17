<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Logistics\LogisticsSetting;
use App\Models\Logistics\OrderShipment;
use App\Models\Order;
use App\Models\StockLocation;
use App\Services\Logistics\LogisticsException;
use App\Services\Logistics\LogisticsService;
use Illuminate\Http\Request;

/** Dispatch › Logistics marketplace: connect GozakMart, and manage an order's courier. */
class LogisticsAdminController extends Controller
{
    public function __construct(private LogisticsService $logistics)
    {
        $this->middleware('permission:Manage dispatch settings', ['only' => ['settings', 'save', 'test']]);
        $this->middleware('permission:Manage dispatch|Pack orders|Manage order', ['only' => ['book', 'refresh', 'switchToOwn']]);
    }

    public function settings()
    {
        $s = LogisticsSetting::query()->first() ?? LogisticsSetting::create([]);
        $branches = StockLocation::active()->orderBy('name')->get(['id', 'name', 'latitude', 'longitude']);
        $recent = OrderShipment::with('order:id,invoice_number,status')->latest('id')->limit(15)->get();
        $counts = OrderShipment::selectRaw('status, COUNT(*) n')->groupBy('status')->pluck('n', 'status');
        $webhookUrl = url('/api/webhooks/logistics');
        $pagetitle = 'Logistics marketplace';
        return view('dispatch.logistics', compact('s', 'branches', 'recent', 'counts', 'webhookUrl', 'pagetitle'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'base_url'                   => 'nullable|url|max:255',
            'api_key'                    => 'nullable|string|max:200',
            'webhook_secret'             => 'nullable|string|max:200',
            'pickup_stock_location_id'   => 'nullable|integer|exists:stock_locations,id',
            'default_weight_g'           => 'required|integer|min:100|max:100000',
            'max_price_increase_percent' => 'required|integer|min:0|max:50',
            'own_delivery_label'         => 'required|string|max:80',
            'own_delivery_eta'           => 'required|string|max:80',
        ]);
        $s = LogisticsSetting::query()->first() ?? new LogisticsSetting();
        // Blank secret fields keep what is stored.
        foreach (['api_key', 'webhook_secret'] as $k) {
            if (blank($data[$k] ?? null)) {
                unset($data[$k]);
            }
        }
        $keyChanged = isset($data['api_key']) && $data['api_key'] !== $s->api_key;
        $s->fill($data + [
            'enabled'              => $request->boolean('enabled'),
            'allow_receiver_pays'  => $request->boolean('allow_receiver_pays'),
            'own_delivery_enabled' => $request->boolean('own_delivery_enabled'),
        ]);
        if ($keyChanged) {
            $s->merchant_status = null; // must be checked again
        }
        $s->save();

        if ($s->base_url && $s->api_key && ($keyChanged || !$s->merchant_status)) {
            try {
                $this->logistics->testConnection();
            } catch (LogisticsException $e) {
                return back()->with('error', 'Saved, but the connection test failed: ' . $e->getMessage());
            }
        }
        $s->refresh();
        return back()->with('success', 'Saved.' . ($s->enabled && !$s->isLive() ? ' Couriers will show at checkout once the marketplace verifies GozakMart.' : ''));
    }

    public function test()
    {
        try {
            $me = $this->logistics->testConnection();
        } catch (LogisticsException $e) {
            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
        return back()->with('success', "Connected to {$me['marketplace']['name']} as {$me['merchant_name']} ({$me['status']}, {$me['environment']} key).");
    }

    public function book(string $order)
    {
        $order = Order::findOrFail($order);
        try {
            $s = $this->logistics->book($order);
        } catch (LogisticsException $e) {
            return back()->with('error', 'Courier not booked: ' . $e->getMessage());
        }
        return back()->with('success', "Courier booked with {$s->provider_name}.");
    }

    public function refresh(string $order)
    {
        $sh = OrderShipment::where('order_id', $order)->firstOrFail();
        try {
            $this->logistics->sync($sh);
        } catch (LogisticsException $e) {
            return back()->with('error', 'Could not refresh: ' . $e->getMessage());
        }
        return back()->with('success', 'Courier status: ' . $sh->fresh()->label() . '.');
    }

    public function switchToOwn(Request $request, string $order)
    {
        $order = Order::findOrFail($order);
        try {
            $this->logistics->switchToOwnRiders($order, $request->input('reason'));
        } catch (LogisticsException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'The courier was cancelled. This order is now on the GozakMart dispatch board.');
    }
}
