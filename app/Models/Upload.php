<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = ['path', 'filename', 'mime_type', 'size'];

    // Add any relationships if necessary, e.g., a product may have a cover image
    public function categories()
    {
        return $this->hasMany(Category::class, 'cover_id');
    }
}
