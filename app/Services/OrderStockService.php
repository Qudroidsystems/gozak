<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Stock;
use App\Models\StockLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Takes stock out of the ledger for orders, and puts it back when an order is
 * cancelled. Before this, placing an order never reduced stock at all.
 *
 *   online payment (paystack, opay)  → stock out when the payment succeeds
 *   pay on delivery, credit, others  → stock out as soon as the order is placed
 *   cancelled                        → stock back in
 *
 * Every entry has a fixed reference (SALE-… / RTN-… per order item), so calling
 * sync() any number of times never double-counts.
 */
class OrderStockService
{
    /** Payment methods where the order only counts once the money has arrived. */
    public const PAY_FIRST = ['paystack', 'opay'];

    public function sync(Order $order): void
    {
        try {
            if ($order->status === 'cancelled') {
                $this->restore($order);
                return;
            }
            if ($this->shouldTakeStock($order)) {
                $this->deduct($order);
            }
        } catch (\Throwable $e) {
            // Never block an order or a payment because of stock bookkeeping.
            Log::error('OrderStockService: ' . $e->getMessage(), ['order_id' => $order->id]);
        }
    }

    public function shouldTakeStock(Order $order): bool
    {
        if (in_array(strtolower((string) $order->payment_method), self::PAY_FIRST, true)) {
            return ($order->getAttributes()['payment_status'] ?? null) === 'paid';
        }
        return true;
    }

    protected function deduct(Order $order): void
    {
        $order->loadMissing('items');
        $location = $this->locationFor($order);
        if (!$location) {
            Log::warning('OrderStockService: no stock location, stock not taken', ['order_id' => $order->id]);
            return;
        }

        DB::transaction(function () use ($order, $location) {
            foreach ($order->items as $item) {
                $ref = $this->ref('SALE', $order, $item->id);
                if (Stock::where('reference_number', $ref)->exists()) {
                    continue;
                }
                $variantId = $item->variation_id ? (int) $item->variation_id : null;
                $before = (int) $location->getProductStock($item->product_id, $variantId);
                Stock::create([
                    'product_id'         => $item->product_id,
                    'product_variant_id' => $variantId,
                    'stock_location_id'  => $location->id,
                    'user_id'            => $order->user_id,
                    'type'               => Stock::TYPE_OUT,
                    'quantity'           => (int) $item->quantity,
                    'previous_quantity'  => $before,
                    'new_quantity'       => $before - (int) $item->quantity,
                    'unit_cost'          => $item->price,
                    'total_cost'         => $item->price * $item->quantity,
                    'reference_number'   => $ref,
                    'reference_type'     => Stock::REFERENCE_SALE,
                    'notes'              => 'Order ' . $order->id,
                    'transaction_date'   => now(),
                ]);
            }
        });
    }

    protected function restore(Order $order): void
    {
        $order->loadMissing('items');
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $sale = Stock::where('reference_number', $this->ref('SALE', $order, $item->id))->first();
                $ref  = $this->ref('RTN', $order, $item->id);
                if (!$sale || Stock::where('reference_number', $ref)->exists()) {
                    continue;   // nothing was taken, or already put back
                }
                $location = StockLocation::find($sale->stock_location_id);
                $before = $location ? (int) $location->getProductStock($sale->product_id, $sale->product_variant_id) : 0;
                Stock::create([
                    'product_id'         => $sale->product_id,
                    'product_variant_id' => $sale->product_variant_id,
                    'stock_location_id'  => $sale->stock_location_id,
                    'user_id'            => auth()->id() ?? $order->user_id,
                    'type'               => Stock::TYPE_RETURN,
                    'quantity'           => (int) $sale->quantity,
                    'previous_quantity'  => $before,
                    'new_quantity'       => $before + (int) $sale->quantity,
                    'reference_number'   => $ref,
                    'reference_type'     => Stock::REFERENCE_RETURN,
                    'notes'              => 'Order ' . $order->id . ' cancelled',
                    'transaction_date'   => now(),
                ]);
            }
        });
    }

    /** The order's store/warehouse (Scan & Shop store, packing location), else the default location. */
    protected function locationFor(Order $order): ?StockLocation
    {
        if ($order->stock_location_id && ($loc = StockLocation::find($order->stock_location_id))) {
            return $loc;
        }
        return StockLocation::getDefault() ?? StockLocation::query()->orderBy('id')->first();
    }

    protected function ref(string $kind, Order $order, $itemId): string
    {
        // reference_number is unique and max 255; order ids are UUIDs.
        return $kind . '-' . $order->id . '-' . $itemId;
    }
}
