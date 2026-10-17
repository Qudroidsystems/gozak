<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Dispatch\RiderAppController;
use App\Services\Dispatch\RiderDeliveryService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Rider API (auth:sanctum) — same rules as the /rider web app, for a Flutter rider app.
 *   GET  /api/rider/runs
 *   GET  /api/rider/runs/{batch}
 *   POST /api/rider/runs/{batch}/stops/{order}/deliver   code | photo(+receiver), cash_collected, cash_method, lat, lng
 *   POST /api/rider/runs/{batch}/stops/{order}/fail      reason, note, lat, lng
 *   POST /api/rider/runs/{batch}/stops/{order}/call
 */
class APIRiderController extends Controller
{
    public function __construct(private RiderDeliveryService $riders)
    {
    }

    public function runs(Request $request)
    {
        return $this->wrap(function () use ($request) {
            $rider = $this->riders->riderFor($request->user()?->id);
            return [
                'rider' => ['id' => $rider->id, 'name' => $rider->name, 'vehicle' => $rider->vehicleLabel()],
                'runs'  => $this->riders->runs($rider)->map(fn ($b) => $this->riders->runPayload($b))->values(),
            ];
        });
    }

    public function run(Request $request, int $batch)
    {
        return $this->wrap(fn () => $this->riders->runPayload(
            $this->riders->run($this->riders->riderFor($request->user()?->id), $batch)
        ));
    }

    public function deliver(Request $request, int $batch, string $order)
    {
        $data = RiderAppController::validateDeliver($request);
        return $this->wrap(function () use ($request, $batch, $order, $data) {
            $rider = $this->riders->riderFor($request->user()?->id);
            $o = $this->riders->deliver($rider, $batch, $order, $data, $request->file('photo'));
            return ['message' => "Stop {$o->stop_sequence} delivered.", 'stop' => $this->riders->stopPayload($o->load('parcels'))];
        });
    }

    public function fail(Request $request, int $batch, string $order)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:120',
            'note'   => 'nullable|string|max:300',
            'lat'    => 'nullable|numeric',
            'lng'    => 'nullable|numeric',
        ]);
        return $this->wrap(function () use ($request, $batch, $order, $data) {
            $rider = $this->riders->riderFor($request->user()?->id);
            $o = $this->riders->fail($rider, $batch, $order, $data['reason'], $data['note'] ?? null, $data['lat'] ?? null, $data['lng'] ?? null);
            return ['message' => 'Marked as not delivered.', 'stop' => $this->riders->stopPayload($o->load('parcels'))];
        });
    }

    public function call(Request $request, int $batch, string $order)
    {
        return $this->wrap(function () use ($request, $batch, $order) {
            $this->riders->logCall($this->riders->riderFor($request->user()?->id), $batch, $order);
            return ['message' => 'Logged.'];
        });
    }

    private function wrap(callable $fn)
    {
        try {
            return response()->json(['success' => true, 'data' => $fn()]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
