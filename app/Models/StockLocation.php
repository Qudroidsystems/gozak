<?php

namespace App\Models;

use App\Models\Stock;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'contact_person',
        'phone',
        'email',
        'is_default',
        'is_active',
        'latitude',
        'longitude',
        'sort_order',
        'notes'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
    ];

    protected $appends = ['total_products', 'total_value'];

    /**
     * Get the stocks for this location.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Get the stock movements for this location.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get all products at this location with their current stock.
     */
    public function products()
    {
        return Product::select('products.*')
            ->join('stocks', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.stock_location_id', $this->id)
            ->selectRaw('SUM(CASE WHEN stocks.type IN ("in", "adjustment", "transfer") THEN stocks.quantity ELSE -stocks.quantity END) as current_stock')
            ->groupBy('products.id')
            ->having('current_stock', '>', 0)
            ->get();
    }

    /**
     * Get the current stock for a specific product.
     */
    public function getProductStock($productId, $variantId = null)
    {
        $query = Stock::where('product_id', $productId)
            ->where('stock_location_id', $this->id);
            
        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        }
        
        $incoming = (clone $query)->whereIn('type', ['in', 'adjustment'])->sum('quantity');
        $outgoing = (clone $query)->whereIn('type', ['out', 'transfer'])->sum('quantity');
        $returns = (clone $query)->where('type', 'return')->sum('quantity');
        $damages = (clone $query)->where('type', 'damage')->sum('quantity');
        
        return $incoming - $outgoing + $returns - $damages;
    }

    /**
     * Get the total value of stock at this location.
     */
    public function getTotalValueAttribute()
    {
        return Stock::where('stock_location_id', $this->id)
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(
                CASE 
                    WHEN stocks.type IN ("in", "adjustment", "transfer") THEN stocks.quantity * COALESCE(stocks.unit_cost, products.price)
                    WHEN stocks.type IN ("out", "damage") THEN -stocks.quantity * COALESCE(stocks.unit_cost, products.price)
                    ELSE 0
                END
            ) as total_value')
            ->value('total_value') ?? 0;
    }

    /**
     * Get the total number of unique products at this location.
     */
    public function getTotalProductsAttribute()
    {
        return Stock::where('stock_location_id', $this->id)
            ->distinct('product_id')
            ->count('product_id');
    }

    /**
     * Get low stock products (below reorder level).
     */
    public function lowStockProducts($threshold = 10)
    {
        return Product::select('products.*')
            ->join('stocks', 'stocks.product_id', '=', 'products.id')
            ->where('stocks.stock_location_id', $this->id)
            ->selectRaw('SUM(CASE WHEN stocks.type IN ("in", "adjustment", "transfer") THEN stocks.quantity ELSE -stocks.quantity END) as current_stock')
            ->groupBy('products.id')
            ->having('current_stock', '>', 0)
            ->having('current_stock', '<=', $threshold)
            ->get();
    }

    /**
     * Get out of stock products.
     */
    public function outOfStockProducts()
    {
        $productIds = Stock::where('stock_location_id', $this->id)
            ->select('product_id')
            ->groupBy('product_id')
            ->selectRaw('SUM(CASE WHEN type IN ("in", "adjustment", "transfer") THEN quantity ELSE -quantity END) as total')
            ->having('total', '<=', 0)
            ->pluck('product_id');
            
        return Product::whereIn('id', $productIds)->get();
    }

    /**
     * Scope for active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default location.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the default location.
     */
    public static function getDefault()
    {
        return static::active()->default()->first();
    }

    /**
     * Check if this is the default location.
     */
    public function isDefault()
    {
        return $this->is_default && $this->is_active;
    }

    /**
     * Get formatted address.
     */
    public function getFormattedAddressAttribute()
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        if ($this->contact_person) $parts[] = "Contact: {$this->contact_person}";
        if ($this->phone) $parts[] = "Phone: {$this->phone}";
        if ($this->email) $parts[] = "Email: {$this->email}";
        
        return implode(' | ', $parts);
    }

    /**
     * Get stock summary.
     */
    public function getStockSummary()
    {
        return [
            'total_products' => $this->total_products,
            'total_value' => $this->total_value,
            'incoming_stock' => $this->stocks()->whereIn('type', ['in', 'transfer'])->sum('quantity'),
            'outgoing_stock' => $this->stocks()->whereIn('type', ['out'])->sum('quantity'),
            'low_stock_count' => $this->lowStockProducts()->count(),
            'out_of_stock_count' => $this->outOfStockProducts()->count(),
        ];
    }

    
}