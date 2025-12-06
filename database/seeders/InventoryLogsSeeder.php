<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Database\Seeder;

class InventoryLogsSeeder extends Seeder
{
    public function run()
    {
        $products = Product::all();
        
        foreach ($products as $product) {
            // Create initial stock log
            if ($product->stock > 0) {
                InventoryLog::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $product->stock,
                    'previous_stock' => 0,
                    'new_stock' => $product->stock,
                    'reference' => 'Initial Stock',
                    'notes' => 'Product created with initial stock',
                    'user_id' => 1
                ]);
            }
            
            // Create some random logs
            $logsCount = rand(2, 5);
            $currentStock = $product->stock;
            
            for ($i = 0; $i < $logsCount; $i++) {
                $type = rand(0, 1) ? 'in' : 'out';
                $quantity = rand(1, 20);
                
                if ($type === 'out' && $quantity > $currentStock) {
                    $quantity = $currentStock;
                }
                
                $newStock = $type === 'in' 
                    ? $currentStock + $quantity 
                    : $currentStock - $quantity;
                
                InventoryLog::create([
                    'product_id' => $product->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'previous_stock' => $currentStock,
                    'new_stock' => $newStock,
                    'reference' => $type === 'in' ? 'Restock' : 'Sale',
                    'notes' => $type === 'in' ? 'Regular restock' : 'Order fulfillment',
                    'user_id' => 1,
                    'created_at' => now()->subDays(rand(1, 30))
                ]);
                
                $currentStock = $newStock;
            }
            
            // Update product stock to match latest log
            $product->update(['stock' => $currentStock]);
        }
    }
}