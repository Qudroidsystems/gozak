<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'abbreviation',
        'description',
    ];

    /**
     * Get products associated with the unit
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_unit')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
