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
        'is_new',
        'is_trending',
        'is_top_rated',
        'category_id',
        'brand_id',
        'is_nsfw',
        'is_active',
    ];

    protected $casts = [
        'price'       => 'decimal:2',
        'cost_price'  => 'decimal:2',
        'sale_price'  => 'decimal:2',
        'is_featured' => 'boolean',
        'is_new'      => 'boolean',
        'is_trending' => 'boolean',
        'is_top_rated'=> 'boolean',
        'is_nsfw'     => 'boolean',
        'is_active'   => 'boolean',
    ];

    // ── $appends ──────────────────────────────────────────────────────────────
    // 'reviews_count' and 'average_rating' are intentionally EXCLUDED here.
    //
    // When the API controller calls ->withCount('reviews')->withAvg('reviews','rating'),
    // Eloquent populates $product->reviews_count and $product->reviews_avg_rating
    // on the model directly as aggregated attributes from a single JOIN — zero
    // extra queries.
    //
    // If we listed them in $appends, the accessor methods below would OVERRIDE
    // those aggregated values with individual COUNT/AVG queries per product,
    // causing N+1 on every product listing. So we keep them out of $appends
    // but still expose the accessor methods so admin panels / non-API code
    // that accesses these properties directly still works correctly.
    // ─────────────────────────────────────────────────────────────────────────
    protected $appends = [
        'current_stock',
        'total_sold',
        'revenue',
        'margin',
        'margin_percent',
    ];

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->barcode)) {
                $product->barcode = 'PROD' . strtoupper(uniqid());
            }
        });

        static::retrieved(function ($product) {
            if (isset($product->stock)) {
                $product->stock = $product->calculateStockFromInventory();
            }
        });
    }

    // ── Stock helpers ─────────────────────────────────────────────────────────

    public function calculateStockFromInventory()
    {
        return $this->calculateCurrentStock();
    }

    public function calculateCurrentStock()
    {
        $total = Stock::where('product_id', $this->id)
            ->selectRaw('
                SUM(CASE
                    WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as total
            ')
            ->value('total') ?? 0;

        return max(0, $total);
    }

    public function getCurrentStockAttribute()
    {
        return $this->calculateCurrentStock();
    }

    public function isLowStock($threshold = 10)
    {
        $stock = $this->calculateCurrentStock();
        return $stock > 0 && $stock <= $threshold;
    }

    public function getStockByLocation($locationId)
    {
        $total = Stock::where('product_id', $this->id)
            ->where('stock_location_id', $locationId)
            ->selectRaw('
                SUM(CASE
                    WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                    WHEN type IN ("out", "damage", "transfer") THEN -quantity
                    ELSE 0
                END) as total
            ')
            ->value('total') ?? 0;

        return max(0, $total);
    }

    public function syncStock()
    {
        $calculated = $this->calculateCurrentStock();

        if ($this->stock != $calculated) {
            $old         = $this->stock;
            $this->stock = $calculated;
            $this->save();

            Log::info('Product stock synced', [
                'product_id'   => $this->id,
                'product_name' => $this->title,
                'old_stock'    => $old,
                'new_stock'    => $calculated,
            ]);

            return true;
        }

        return false;
    }

    // ── Rating accessors ──────────────────────────────────────────────────────
    //
    // These are NOT in $appends so they do not fire automatically on every
    // model serialisation. They are available for direct property access in
    // admin/blade code (e.g. $product->reviews_count) but the API controller
    // deliberately loads the aggregate via withCount/withAvg, which Eloquent
    // stores under the same attribute name and takes precedence over the
    // accessor when already set on the model.
    //
    // Rule of thumb:
    //   API listing / detail  → always use ->withCount() / ->withAvg()
    //   Admin panel / one-off → accessor fires a single query, which is fine

    public function getReviewsCountAttribute()
    {
        // If withCount('reviews') already populated this, Eloquent returns
        // that cached value before calling this accessor — no extra query.
        return $this->reviews()->count();
    }

    public function getAverageRatingAttribute()
    {
        // Same as above: withAvg stores as 'reviews_avg_rating', not
        // 'average_rating', so this accessor is only reached in non-API code.
        return round((float) ($this->reviews()->avg('rating') ?? 0), 1);
    }

    // ── Financial accessors ───────────────────────────────────────────────────

    public function getTotalSoldAttribute()
    {
        return $this->orderItems()->sum('quantity');
    }

    public function getRevenueAttribute()
    {
        $price = $this->sale_price ?? $this->price;
        return $this->getTotalSoldAttribute() * $price;
    }

    public function getMarginAttribute()
    {
        if (!$this->cost_price) return 0;
        $selling = $this->sale_price ?? $this->price;
        return $selling - $this->cost_price;
    }

    public function getMarginPercentAttribute()
    {
        if (!$this->cost_price || $this->cost_price <= 0) return 0;
        $selling = $this->sale_price ?? $this->price;
        return (($selling - $this->cost_price) / $this->cost_price) * 100;
    }

    // ── Relationships ─────────────────────────────────────────────────────────

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

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function lightningDeal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Models\LightningDeal::class);
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

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        if (\Schema::hasColumn($this->getTable(), 'is_active')) {
            return $query->where('is_active', true);
        }
        return $query;
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive')
                     ->orWhere('is_active', false);
    }

    public function scopeLowStock($query)
    {
        return $query->where('stock', '>', 0)
                     ->where('stock', '<=', 10);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 10);
    }

    public function scopeNew($query)
    {
        return $query->where('is_new', true);
    }

    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    public function scopeTopRated($query)
    {
        return $query->where('is_top_rated', true);
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price')
                     ->where('sale_price', '>', 0)
                     ->whereColumn('sale_price', '<', 'price');
    }

    public function scopeByBarcode($query, $barcode)
    {
        return $query->where('barcode', $barcode);
    }
}
