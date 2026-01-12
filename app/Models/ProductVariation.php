<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'barcode', // Add this
        'price',
        'sale_price',
        'attributes',
        'image'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'attributes' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate barcode if not provided
        static::creating(function ($variation) {
            if (empty($variation->barcode)) {
                $variation->barcode = 'VAR' . strtoupper(Str::random(10));
            }
        });

        // Make sure SKU is unique if not provided
        static::creating(function ($variation) {
            if (empty($variation->sku)) {
                $baseSku = $variation->product->sku ?? 'PROD';
                $variation->sku = $baseSku . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the display name with attributes
     */
    public function getDisplayNameAttribute()
    {
        $productName = $this->product->title ?? '';
        $attributes = $this->attributes ? implode(', ', $this->attributes) : '';

        return $productName . ($attributes ? ' (' . $attributes . ')' : '');
    }

    /**
     * Get the effective price (sale_price if available, otherwise price)
     */
    public function getEffectivePriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * Scope to search by barcode
     */
    public function scopeByBarcode($query, $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    /**
     * Check if variation is in stock
     */
    public function getIsInStockAttribute()
    {
        return $this->stock > 0;
    }

    /**
     * Check if variation is on sale
     */
    public function getIsOnSaleAttribute()
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }
}
