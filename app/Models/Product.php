<?php
namespace App\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'title',
        'sku',
        'stock', // This gets updated from inventory system
        'price',
        'sale_price',
        'thumbnail',
        'description',
        'product_type',
        'sold_quantity',
        'is_featured',
        'category_id',
        'brand_id',
        'is_nsfw',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_nsfw' => 'boolean',
    ];

    /**
     * Boot the model - REMOVED automatic stock logging
     * Inventory system handles all stock tracking
     */
    protected static function boot()
    {
        parent::boot();
        
        // No automatic stock logging here
        // InventoryController handles all stock updates
    }
    
    /**
     * Calculate stock from inventory (for verification/display)
     */
    public function calculateStockFromInventory()
    {
        $totalStock = Stock::where('product_id', $this->id)
            ->selectRaw('
                SUM(CASE 
                    WHEN type IN ("in", "adjustment", "transfer_in") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as total
            ')
            ->value('total') ?? 0;
        
        return max(0, $totalStock);
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
        // Simple approach - check for common column names
        // Most Laravel applications use 'is_active' or 'status'
        
        // If you're not sure, let's check safely
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
}