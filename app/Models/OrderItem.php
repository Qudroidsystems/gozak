<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'title',
        'price',
        'quantity',
        'variation_id',
        'image',
        'brand_name',
        'selected_variation',
        'sku',
        'weight',
        'dimensions',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'selected_variation' => 'array',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->price * $this->quantity;
    }

    public function getFormattedPriceAttribute(): string
    {
        return '₦' . number_format($this->price, 2);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return '₦' . number_format($this->total_price, 2);
    }

    public function getVariationTextAttribute(): string
    {
        if (empty($this->selected_variation)) {
            return '';
        }

        $variations = json_decode($this->selected_variation, true) ?? [];
        return implode(', ', array_map(function($key, $value) {
            return "{$key}: {$value}";
        }, array_keys($variations), $variations));
    }

    public function getItemSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'brand' => $this->brand_name,
            'image' => $this->image,
            'variations' => $this->variation_text,
            'sku' => $this->sku,
        ];
    }
}