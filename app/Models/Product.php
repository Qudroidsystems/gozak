<?php
namespace App\Models;

use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Traits\HasInventoryLog;
use App\Models\ProductAttribute;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{

    use HasInventoryLog;

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Automatically log initial stock when creating a product
        static::created(function ($product) {
            if ($product->stock > 0) {
                $product->addStock($product->stock, 'Initial stock', 'Product created');
            }
        });
        
        // Log stock changes when updating
        static::updating(function ($product) {
            $originalStock = $product->getOriginal('stock');
            $newStock = $product->stock;
            
            if ($originalStock !== $newStock) {
                $difference = $newStock - $originalStock;
                $type = $difference > 0 ? 'in' : 'out';
                $quantity = abs($difference);
                
                $product->logInventoryChange(
                    $type,
                    $quantity,
                    'Manual adjustment',
                    'Stock updated from ' . $originalStock . ' to ' . $newStock
                );
            }
        });
    }
    
    protected $fillable = [
        'title',
        'sku',
        'stock',
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

   // In your Product model (App\Models\Product.php)
    public function inventoryLogs()
    {
        // Assuming you have an InventoryLog model
        return $this->hasMany(InventoryLog::class);
    }
}