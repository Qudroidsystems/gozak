<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PromoBanner extends Model
{
    protected $fillable = [
        'badge_text',
        'title',
        'subtitle',
        'cta_text',
        'cta_route',
        'image_url',
        'gradient_start',
        'gradient_end',
        'accent_color',
        'target_screen',
        'active',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'sort_order' => 'integer',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Only banners that are active and within their schedule window.
     */
    public function scopeLive($query)
    {
        $now = now();

        return $query->where('active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeForScreen($query, string $screen)
    {
        return $query->where(function ($q) use ($screen) {
            $q->where('target_screen', $screen)
              ->orWhere('target_screen', 'all');
        });
    }

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getFullImageUrlAttribute(): ?string
    {
        if (!$this->image_url) return null;
        return url(Storage::url($this->image_url));
    }
}
