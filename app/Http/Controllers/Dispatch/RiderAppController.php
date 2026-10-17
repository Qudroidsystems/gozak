<?php

namespace App\Http\Controllers\Dispatch;

use App\Http\Controllers\Controller;
use App\Models\Dispatch\DispatchSetting;
use App\Services\Dispatch\RiderDeliveryService;
use Illuminate\Http\Request;
use RuntimeException;

/** Mobile web app for riders: /rider */
class RiderAppController extends Controller
{
    public function __construct(private RiderDeliveryService $riders)
    {
        $this->middleware('permission:Deliver orders');
    }

    public function index()
    {
        try {
            $rider = $this->riders->riderFor(auth()->id());
        } catch (RuntimeException $e) {
            return view('rider.index', ['rider' => null, 'runs' => collect(), 'problem' => $e->getMessage()]);
        }
        $runs = $this->riders->runs($rider);
        return view('rider.index', ['rider' => $rider, 'runs' => $runs, 'problem' => null]);
    }

    public function run(int $batch)
    {
        try {
            $rider = $this->riders->riderFor(auth()->id());
            $run   = $this->riders->run($rider, $batch);
        } catch (RuntimeException $e) {
            return redirect()->route('rider.index')->with('error', $e->getMessage());
        }
        $settings = DispatchSetting::current();
        return view('rider.run', compact('rider', 'run', 'settings'));
    }

    public function deliver(Request $request, int $batch, string $order)
    {
        $data = $this->validateDeliver($request);
        try {
            $rider = $this->riders->riderFor(auth()->id());
            $o = $this->riders->deliver($rider, $batch, $order, $data, $request->file('photo'));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->with('open_stop', $order)->withInput();
        }
        return back()->with('success', "Stop {$o->stop_sequence} delivered — order {$o->ref}.");
    }

    public function fail(Request $request, int $batch, string $order)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:120',
            'note'   => 'nullable|string|max:300',
            'lat'    => 'nullable|numeric|between:-90,90',
            'lng'    => 'nullable|numeric|between:-180,180',
        ]);
        try {
            $rider = $this->riders->riderFor(auth()->id());
            $o = $this->riders->fail($rider, $batch, $order, $data['reason'], $data['note'] ?? null, $data['lat'] ?? null, $data['lng'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', "Stop {$o->stop_sequence} marked as not delivered. Bring the parcels back to the store.");
    }

    public function call(int $batch, string $order)
    {
        try {
            $this->riders->logCall($this->riders->riderFor(auth()->id()), $batch, $order);
        } catch (RuntimeException $e) {
            return response()->json(['ok' => false], 422);
        }
        return response()->json(['ok' => true]);
    }

    public static function validateDeliver(Request $request): array
    {
        return $request->validate([
            'code'           => 'nullable|string|max:8',
            'receiver'       => 'nullable|string|max:120',
            'cash_collected' => 'nullable|numeric|min:0|max:100000000',
            'cash_method'    => 'nullable|in:cash,transfer,pos',
            'note'           => 'nullable|string|max:300',
            'lat'            => 'nullable|numeric|between:-90,90',
            'lng'            => 'nullable|numeric|between:-180,180',
            'photo'          => 'nullable|image|max:8192',
        ]);
    }
}
