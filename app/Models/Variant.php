<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Relationships
    public function values(): HasMany
    {
        return $this->hasMany(VariantValue::class);
    }

    // Scopes
    public function scopeWithValues($query)
    {
        return $query->with('values');
    }

    public function scopeOrderByName($query, $direction = 'asc')
    {
        return $query->orderBy('name', $direction);
    }

    // Helpers
    public function displayName()
    {
        return ucfirst($this->name);
    }
}
