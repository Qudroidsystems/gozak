<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;


    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
      /**
     * Get the product that owns the stock transaction
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who made the stock transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Stock.php
    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }
}
