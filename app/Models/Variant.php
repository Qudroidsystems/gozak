<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A reusable option type in the attribute library: Size, Colour, Storage …
 *
 * display_type tells the app how to draw it:
 *   button — text chips        size  — compact size boxes
 *   color  — colour swatches   image — photo chips (uses the variation photo)
 */
class Variant extends Model
{
    use HasFactory;

    public const DISPLAY_TYPES = ['button', 'size', 'color', 'image'];

    /** Cache key for the name → library lookup used by the product API. */
    public const LOOKUP_CACHE_KEY = 'attribute_library_lookup_v1';

    protected $fillable = ['name', 'slug', 'display_type', 'hint', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Variant $variant) {
            if (empty($variant->slug)) {
                $variant->slug = Str::slug($variant->name);
            }
            if (!in_array($variant->display_type, self::DISPLAY_TYPES, true)) {
                $variant->display_type = 'button';
            }
        });

        static::saved(fn () => Cache::forget(self::LOOKUP_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::LOOKUP_CACHE_KEY));
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function values(): HasMany
    {
        return $this->hasMany(VariantValue::class)->orderBy('sort_order')->orderBy('id');
    }

    public function productAttributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // Kept from the earlier version of this model
    public function scopeWithValues($query)
    {
        return $query->with('values');
    }

    public function scopeOrderByName($query, $direction = 'asc')
    {
        return $query->orderBy('name', $direction);
    }

    // ── Lookup used by the product API ──────────────────────────────────────

    /**
     * name (lower-case) → ['id', 'name', 'display_type', 'hint', 'values' => [value lower-case → hex]]
     * One cached query for the whole library, so formatting a product page
     * never adds per-attribute queries. Returns [] if the tables don't exist yet.
     */
    public static function lookup(): array
    {
        return Cache::remember(self::LOOKUP_CACHE_KEY, now()->addHours(6), function () {
            $out = [];
            try {
                $variants = static::with('values')->get();
            } catch (\Throwable $e) {
                return [];   // migrations not run yet
            }
            foreach ($variants as $variant) {
                $values = [];
                foreach ($variant->values as $v) {
                    $values[mb_strtolower(trim($v->value))] = $v->color_hex;
                }
                $entry = [
                    'id'           => $variant->id,
                    'name'         => $variant->name,
                    'display_type' => $variant->display_type,
                    'hint'         => $variant->hint,
                    'values'       => $values,
                ];
                $key = mb_strtolower(trim($variant->name));
                $out[$key] = $entry;
                // Let "Color" and "Colour" find the same entry.
                if (in_array($key, ['colour', 'color'], true)) {
                    $out['colour'] = $entry;
                    $out['color']  = $entry;
                }
            }
            return $out;
        });
    }

    public function displayName(): string
    {
        return ucfirst($this->name);
    }
}
