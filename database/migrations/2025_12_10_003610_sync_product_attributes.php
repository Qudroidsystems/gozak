<?php

use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Log::info('Starting product attributes sync migration');
        
        // Get all variable products with variations
        $products = Product::where('product_type', 'variable')
            ->with('variations')
            ->get();
            
        $totalProducts = $products->count();
        $processedProducts = 0;
        $totalAttributesCreated = 0;
        
        Log::info("Found {$totalProducts} variable products to process");
            
        foreach ($products as $product) {
            $processedProducts++;
            
            if ($product->variations->isEmpty()) {
                Log::info("Product ID {$product->id}: No variations found");
                continue;
            }
            
            Log::info("Processing Product ID: {$product->id} - {$product->title}");
            
            $attributes = [];
            
            foreach ($product->variations as $variation) {
                $varAttributes = $variation->attributes;
                
                if (is_string($varAttributes) && $varAttributes !== '') {
                    try {
                        $varAttributes = json_decode($varAttributes, true);
                    } catch (\Exception $e) {
                        Log::error("Error decoding attributes for variation {$variation->id}: " . $e->getMessage());
                        continue;
                    }
                }
                
                if (is_array($varAttributes) && !empty($varAttributes)) {
                    foreach ($varAttributes as $key => $value) {
                        if (!isset($attributes[$key])) {
                            $attributes[$key] = [];
                        }
                        
                        if (!in_array($value, $attributes[$key])) {
                            $attributes[$key][] = $value;
                        }
                    }
                }
            }
            
            // Delete existing attributes
            ProductAttribute::where('product_id', $product->id)->delete();
            
            // Create new attributes
            $attributesCreated = 0;
            foreach ($attributes as $name => $values) {
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'name' => $name,
                    'values' => $values,
                ]);
                $attributesCreated++;
                Log::info("Created attribute: {$name} = " . implode(', ', $values));
            }
            
            $totalAttributesCreated += $attributesCreated;
            Log::info("Created {$attributesCreated} attributes for product ID {$product->id}");
            
            // Log progress every 10 products
            if ($processedProducts % 10 === 0) {
                Log::info("Progress: {$processedProducts}/{$totalProducts} products processed");
            }
        }
        
        Log::info("Migration completed. Processed {$processedProducts} products, created {$totalAttributesCreated} attributes.");
    }

    public function down()
    {
        // Optional: You can rollback by truncating product_attributes table
        // DB::table('product_attributes')->truncate();
    }
};