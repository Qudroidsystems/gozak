<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VariantValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'variant_id',
        'value',
    ];

    // Relationships
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function productVariants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_combinations');
    }

    // Scopes
    public function scopeForVariant($query, $variantName)
    {
        return $query->whereHas('variant', function($q) use ($variantName) {
            $q->where('name', $variantName);
        });
    }

    public function scopeOrderByValue($query, $direction = 'asc')
    {
        return $query->orderBy('value', $direction);
    }

    // Helpers
    public function fullName()
    {
        return $this->variant->displayName() . ': ' . $this->value;
    }
}
