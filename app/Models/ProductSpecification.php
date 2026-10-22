<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One row of a product's "Specifications" list, e.g. Material → 100% Cotton. */
class ProductSpecification extends Model
{
    protected $fillable = ['product_id', 'label', 'value', 'group', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
