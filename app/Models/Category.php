<?php
namespace App\Models;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'image', 'parent_id', 'is_featured', 'is_nsfw'];  // NEW: Add is_nsfw

    protected $casts = [
        'is_featured' => 'boolean',
        'is_nsfw' => 'boolean',  // NEW: Cast as boolean for safe_mode queries
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'brand_categories', 'category_id', 'brand_id');
    }
}