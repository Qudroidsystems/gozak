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
        'size_guide',
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
        'size_guide'  => 'array',
    ];

    // ── $appends ──────────────────────────────────────────────────────────────
    // Intentionally empty. current_stock / total_sold / revenue / margin used to
    // be appended, which ran 3 extra queries for EVERY product each time a
    // product was turned into JSON (admin product table: ~5 queries per row).
    // Nothing reads them from JSON; the accessors below still work when you
    // access them directly ($product->current_stock), and they use
    // pre-loaded aggregates (ledger_stock, sold_total) when the query has them.
    protected $appends = [];

    /** Per-instance cache so repeated $product->current_stock reads cost one query at most. */
    protected ?int $ledgerStockCache = null;

    // ── Boot ──────────────────────────────────────────────────────────────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->barcode)) {
                $product->barcode = 'PROD' . strtoupper(uniqid());
            }
        });

        // (A `retrieved` hook used to re-add the stock ledger for every product
        // loaded: one extra query per product on every page and API call.
        // products.stock is already kept in sync by StockObserver and the
        // stocks-table DB triggers, so it was removed.)
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
        // Pre-loaded by the query (see scopeWithLedgerStock) → no extra query.
        if (array_key_exists('ledger_stock', $this->attributes)) {
            return max(0, (int) $this->attributes['ledger_stock']);
        }
        return $this->ledgerStockCache ??= (int) $this->calculateCurrentStock();
    }

    /**
     * Adds `ledger_stock` (live stock from the ledger) as a subquery, so
     * listing many products costs one query instead of one per product.
     */
    public function scopeWithLedgerStock($query)
    {
        return $query->addSelect(['ledger_stock' => Stock::query()
            ->selectRaw('COALESCE(SUM(CASE
                WHEN type IN ("in", "adjustment", "transfer_in", "return") THEN quantity
                WHEN type IN ("out", "damage", "transfer") THEN -quantity
                ELSE 0 END), 0)')
            ->whereColumn('stocks.product_id', 'products.id'),
        ]);
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
        // withSum('orderItems as sold_total', 'quantity') → no extra query
        if (array_key_exists('sold_total', $this->attributes)) {
            return (int) $this->attributes['sold_total'];
        }
        return $this->orderItems()->sum('quantity');
    }

    public function getRevenueAttribute()
    {
        $price = $this->sale_price ?? $this->price;
        return $this->total_sold * $price;
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

    /** Photos + videos in display order (primary first, then sort_order). */
    public function media()
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

    /** Photos only, in display order. */
    public function photos()
    {
        return $this->hasMany(ProductImage::class)->photos()->ordered();
    }

    /** Short product videos, in display order. */
    public function videos()
    {
        return $this->hasMany(ProductImage::class)->videos()->ordered();
    }

    /** Jumia-style specifications list (Material, Warranty …). */
    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order')->orderBy('id');
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
