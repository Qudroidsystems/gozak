<?php

namespace App\Observers;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockObserver
{
    /**
     * Handle the Stock "created" event.
     */
    public function created(Stock $stock): void
    {
        $this->updateProductStock($stock->product_id);
    }

    /**
     * Handle the Stock "updated" event.
     */
    public function updated(Stock $stock): void
    {
        $this->updateProductStock($stock->product_id);
    }

    /**
     * Handle the Stock "deleted" event.
     */
    public function deleted(Stock $stock): void
    {
        $this->updateProductStock($stock->product_id);
    }

    /**
     * Update product stock after stock transaction
     */
    private function updateProductStock($productId): void
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                return;
            }
            
            // Calculate total stock from all transactions
            $totalStock = Stock::where('product_id', $productId)
                ->selectRaw('
                    SUM(CASE 
                        WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                        WHEN type IN ("out", "damage", "transfer") THEN -quantity
                        ELSE 0
                    END) as total
                ')
                ->value('total') ?? 0;
            
            $calculatedStock = max(0, $totalStock);
            
            // Update if changed
            if ($product->stock != $calculatedStock) {
                $oldStock = $product->stock;
                
                // Use direct DB update to avoid model events
                DB::table('products')
                    ->where('id', $productId)
                    ->update(['stock' => $calculatedStock]);
                
                Log::info("Observer: Updated product {$productId} stock: {$oldStock} → {$calculatedStock}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to update product stock in observer: " . $e->getMessage());
        }
    }
}