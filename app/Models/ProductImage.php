<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A product photo or short video (the table is still called product_images
 * so existing code keeps working).
 *
 * Photos can be tied to an option value (attribute_name = "Colour",
 * attribute_value = "Red") so the app shows the red photos when Red is picked.
 */
class ProductImage extends Model
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    protected $fillable = [
        'product_id',
        'image_path',
        'media_type',
        'thumb_path',
        'poster_path',
        'duration_seconds',
        'attribute_name',
        'attribute_value',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary'       => 'boolean',
        'sort_order'       => 'integer',
        'duration_seconds' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    /** Photos only — rows created before media_type existed count as photos. */
    public function scopePhotos($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('media_type')->orWhere('media_type', self::TYPE_IMAGE);
        });
    }

    public function scopeVideos($query)
    {
        return $query->where('media_type', self::TYPE_VIDEO);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isVideo(): bool
    {
        return $this->media_type === self::TYPE_VIDEO;
    }

    public static function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return url(Storage::url(preg_replace('#^/?storage/#', '', $path)));
    }

    public function getUrlAttribute(): ?string
    {
        return self::publicUrl($this->image_path);
    }

    public function getThumbUrlAttribute(): ?string
    {
        return self::publicUrl($this->thumb_path ?: ($this->isVideo() ? $this->poster_path : $this->image_path));
    }

    public function getPosterUrlAttribute(): ?string
    {
        return self::publicUrl($this->poster_path);
    }

    /** Shape sent to the app (GET /api/products/{id} → media[]). */
    public function toMediaArray(): array
    {
        return [
            'id'               => (int) $this->id,
            'type'             => $this->isVideo() ? self::TYPE_VIDEO : self::TYPE_IMAGE,
            'url'              => $this->url,
            'thumb_url'        => $this->thumb_url,
            'poster_url'       => $this->poster_url,
            'duration_seconds' => $this->duration_seconds,
            'attribute_name'   => $this->attribute_name,
            'attribute_value'  => $this->attribute_value,
            'alt'              => $this->alt_text,
            'is_primary'       => (bool) $this->is_primary,
        ];
    }
}
