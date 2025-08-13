<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'variant_value_id',
    ];

    /**
     * Get the product variant that owns this value
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the variant value
     */
    public function variantValue()
    {
        return $this->belongsTo(VariantValue::class);
    }
}
