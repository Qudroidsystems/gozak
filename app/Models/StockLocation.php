<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockLocation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_locations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'location_name',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The relationships to always load.
     *
     * @var array
     */
    protected $with = ['stocks'];

    // Relationships

    /**
     * Get the stocks associated with this stock location.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'stock_location_id');
    }

    

    // Helpers

    /**
     * Get the total stock quantity at this location.
     */
    public function totalStock()
    {
        return $this->stocks->sum('quantity');
    }

    /**
     * Get all products stored at this location.
     */
    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            Stock::class,
            'stock_location_id', // Foreign key on stocks table
            'id',                // Foreign key on products table
            'id',                // Local key on stock_locations table
            'product_variant_id' // Local key on stocks table, linking to product_variants
        )->distinct();
    }
}
