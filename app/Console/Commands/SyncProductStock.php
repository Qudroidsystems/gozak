<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductStock extends Command
{
    protected $signature = 'products:sync-stock 
                            {--fix : Fix any inconsistencies in product stock}';
    
    protected $description = 'Sync all product stock from inventory transactions';

    public function handle()
    {
        $this->info('Syncing product stock from inventory...');
        
        $products = Product::all();
        $bar = $this->output->createProgressBar(count($products));
        
        $updatedCount = 0;
        $errors = [];
        
        foreach ($products as $product) {
            try {
                // Calculate stock from inventory
                $totalStock = Stock::where('product_id', $product->id)
                    ->selectRaw('
                        SUM(CASE 
                            WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                            WHEN type IN ("out", "damage", "transfer") THEN -quantity
                            ELSE 0
                        END) as total
                    ')
                    ->value('total') ?? 0;
                
                $calculatedStock = max(0, $totalStock);
                
                $oldStock = $product->stock;
                
                if ($oldStock != $calculatedStock) {
                    // Update product stock
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['stock' => $calculatedStock]);
                    
                    $updatedCount++;
                    
                    if ($this->option('fix')) {
                        $this->info(" ✓ Fixed product {$product->id} ({$product->title}): {$oldStock} → {$calculatedStock}");
                    } else {
                        $this->info(" Updated product {$product->id} ({$product->title}): {$oldStock} → {$calculatedStock}");
                    }
                    
                    Log::info("SyncProductStock: Updated product {$product->id} stock: {$oldStock} → {$calculatedStock}");
                }
            } catch (\Exception $e) {
                $errors[] = "Product ID {$product->id}: " . $e->getMessage();
                $this->error(" Error with product {$product->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        if ($updatedCount > 0) {
            $this->info("✅ Successfully synced {$updatedCount} product(s)!");
        } else {
            $this->info("✅ All product stocks are already in sync!");
        }
        
        if (!empty($errors)) {
            $this->warn("⚠️  Encountered " . count($errors) . " error(s):");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }
        
        // Run a consistency check
        $this->checkConsistency();
        
        return 0;
    }
    
    /**
     * Check data consistency
     */
    private function checkConsistency()
    {
        $this->newLine();
        $this->info("Running consistency checks...");
        
        // Check for negative stock
        $negativeStock = Product::where('stock', '<', 0)->count();
        if ($negativeStock > 0) {
            $this->warn("⚠️  Found {$negativeStock} product(s) with negative stock");
        }
        
        // Check for products with no stock records
        $noStockRecords = Product::whereDoesntHave('stocks')->count();
        if ($noStockRecords > 0) {
            $this->warn("⚠️  Found {$noStockRecords} product(s) with no stock transactions");
        }
        
        // Check total product count
        $totalProducts = Product::count();
        $this->info("📊 Total products in system: {$totalProducts}");
        
        // Check average stock
        $avgStock = Product::avg('stock');
        $this->info("📊 Average stock per product: " . round($avgStock, 2));
    }
}