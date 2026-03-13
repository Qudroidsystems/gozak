<?php

namespace App\Models;

use App\Models\Brand;
use App\Models\Stock;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\StockMovement;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    protected $fillable = [
        'title',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'sale_price',
        'thumbnail',
        'description',
        'product_type',
        'stock',
        'sold_quantity',
        'is_featured',
        'category_id',
        'brand_id',
        'is_nsfw',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_nsfw' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'current_stock',
        'total_sold',
        'reviews_count',
        'average_rating',
        'revenue',
        'margin',
        'margin_percent',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate barcode if not provided
        static::creating(function ($product) {
            if (empty($product->barcode)) {
                $product->barcode = 'PROD' . strtoupper(uniqid());
            }
        });

        // Calculate stock from inventory when accessing stock attribute
        static::retrieved(function ($product) {
            if (isset($product->stock)) {
                $product->stock = $product->calculateStockFromInventory();
            }
        });
    }

    /**
     * Calculate stock from inventory (for verification/display)
     */
    public function calculateStockFromInventory()
    {
        $totalStock = Stock::where('product_id', $this->id)
            ->selectRaw('
                SUM(CASE
                    WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as total
            ')
            ->value('total') ?? 0;

        return max(0, $totalStock);
    }

    /**
     * Get current stock from inventory (always fresh calculation)
     */
    public function getCurrentStockAttribute()
    {
        return $this->calculateStockFromInventory();
    }

    /**
     * Get margin (selling price - cost price)
     */
    public function getMarginAttribute()
    {
        if (!$this->cost_price) {
            return 0;
        }

        $sellingPrice = $this->sale_price ?? $this->price;
        return $sellingPrice - $this->cost_price;
    }

    /**
     * Get margin percentage
     */
    public function getMarginPercentAttribute()
    {
        if (!$this->cost_price || $this->cost_price <= 0) {
            return 0;
        }

        $sellingPrice = $this->sale_price ?? $this->price;
        $margin = $sellingPrice - $this->cost_price;
        return ($margin / $this->cost_price) * 100;
    }

    /**
     * Stock relationship
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'product_brand');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    // Add the missing relationship for order items
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Add total sold calculation
    public function getTotalSoldAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }

    // Add reviews count accessor
    public function getReviewsCountAttribute()
    {
        return $this->reviews()->count();
    }

    // Add average rating accessor
    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    // Add revenue calculation
    public function getRevenueAttribute()
    {
        $totalSold = $this->getTotalSoldAttribute();
        $price = $this->sale_price ?? $this->price;
        return $totalSold * $price;
    }

    // Inventory logs relationship
    public function inventoryLogs()
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    /**
     * Scope for active products.
     */
    public function scopeActive($query)
    {
        if (\Schema::hasColumn($this->getTable(), 'is_active')) {
            return $query->where('is_active', true);
        }

        // If no is_active column, just return all products
        return $query;
    }

    /**
     * Scope for inactive products.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive')
                     ->orWhere('is_active', false);
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query)
    {
        return $query->where('stock', '>', 0)
                     ->where('stock', '<=', 10);
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    /**
     * Scope for in stock products
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 10);
    }

    public function units()
    {
        return $this->belongsToMany(Unit::class, 'product_unit')
                    ->withPivot('quantity_per_unit')
                    ->withTimestamps();
    }

    public function primaryUnit()
    {
        return $this->belongsToMany(Unit::class, 'product_unit')
                    ->withPivot('quantity_per_unit')
                    ->orderBy('id')
                    ->limit(1);
    }

     /**
     * Calculate current stock from inventory transactions
     */
    public function calculateCurrentStock()
    {
        $totalStock = Stock::where('product_id', $this->id)
            ->selectRaw('
                SUM(CASE
                    WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as total
            ')
            ->value('total') ?? 0;

        return max(0, $totalStock);
    }

    /**
     * Check if product is low stock
     */
    public function isLowStock($threshold = 10)
    {
        $currentStock = $this->calculateCurrentStock();
        return $currentStock > 0 && $currentStock <= $threshold;
    }

    /**
     * Get stock by location
     */
    public function getStockByLocation($locationId)
    {
        $totalStock = Stock::where('product_id', $this->id)
            ->where('stock_location_id', $locationId)
            ->selectRaw('
                SUM(CASE
                    WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as total
            ')
            ->value('total') ?? 0;

        return max(0, $totalStock);
    }

    /**
     * Sync this product's stock with inventory transactions
     */
    public function syncStock()
    {
        $calculatedStock = $this->calculateCurrentStock();

        if ($this->stock != $calculatedStock) {
            $oldStock = $this->stock;
            $this->stock = $calculatedStock;
            $this->save();

            Log::info("Product stock synced", [
                'product_id' => $this->id,
                'product_name' => $this->title,
                'old_stock' => $oldStock,
                'new_stock' => $calculatedStock
            ]);

            return true;
        }

        return false;
    }

    /**
     * Scope for searching by barcode
     */
    public function scopeByBarcode($query, $barcode)
    {
        return $query->where('barcode', $barcode);
    }


    public function lightningDeal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\LightningDeal::class);
    }
}
