<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/** One value in the attribute library, e.g. Size → "XL", Colour → "Red" (#E53935). */
class VariantValue extends Model
{
    use HasFactory;

    protected $fillable = ['variant_id', 'value', 'color_hex', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    protected static function booted(): void
    {
        static::saving(function (VariantValue $value) {
            $value->value = trim($value->value);
            if ($value->color_hex) {
                $hex = strtoupper(ltrim(trim($value->color_hex), '#'));
                $value->color_hex = preg_match('/^[0-9A-F]{6}([0-9A-F]{2})?$/', $hex) ? '#' . $hex : null;
            }
        });

        static::saved(fn () => Cache::forget(Variant::LOOKUP_CACHE_KEY));
        static::deleted(fn () => Cache::forget(Variant::LOOKUP_CACHE_KEY));
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function scopeForVariant($query, $variantName)
    {
        return $query->whereHas('variant', fn ($q) => $q->where('name', $variantName));
    }

    public function scopeOrderByValue($query, $direction = 'asc')
    {
        return $query->orderBy('value', $direction);
    }

    public function fullName(): string
    {
        return $this->variant->displayName() . ': ' . $this->value;
    }
}
