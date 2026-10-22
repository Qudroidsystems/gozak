<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = ['product_id', 'variant_id', 'name', 'display_type', 'values', 'sort_order'];

    protected $casts = [
        'values'     => 'array',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** The attribute-library entry this came from (null for free-typed attributes). */
    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
