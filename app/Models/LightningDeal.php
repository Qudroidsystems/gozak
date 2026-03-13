<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LightningDeal extends Model
{
    protected $fillable = [
        'product_id',
        'discount_percentage',
        'stock_limit',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'is_active'  => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /** Only deals that are active and within their time window */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('starts_at')
                           ->orWhere('starts_at', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('ends_at')
                           ->orWhere('ends_at', '>', now());
                     });
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    /** How many items are left in this deal (respects stock_limit) */
    public function getStockLeftAttribute(): int
    {
        $productStock = $this->product?->stock ?? 0;
        if ($this->stock_limit === null) {
            return $productStock;
        }
        return min($this->stock_limit, $productStock);
    }

    /** Whether this deal has expired */
    public function getIsExpiredAttribute(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }
}
