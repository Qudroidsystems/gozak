<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'selected_variation' => 'array', // Store JSON for variation attributes
    ];

    /**
     * Get the order this item belongs to.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product for this item.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}