<?php
// app/Models/PromoBanner.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PromoBanner extends Model
{
    protected $fillable = [
        'badge_text',
        'title',
        'subtitle',
        'cta_text',
        'cta_route',
        'image_url',
        'lottie_asset',
        'gradient_start',
        'gradient_end',
        'accent_color',
        'target_screen',
        'active',
        'starts_at',
        'ends_at',
        'sort_order',
        'show_once_daily',
    ];

    protected $casts = [
        'active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
        'show_once_daily' => 'boolean',
    ];

    // ── Accessors ────────────────────────────────────────────────────────────

    public function getFullImageUrlAttribute(): ?string
    {
        if (!$this->image_url) {
            return null;
        }
        return asset('storage/' . $this->image_url);
    }

    public function getIsActiveNowAttribute(): bool
    {
        $now = now();

        if (!$this->active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at > $now) {
            return false;
        }

        if ($this->ends_at && $this->ends_at < $now) {
            return false;
        }

        return true;
    }

    public function getStatusAttribute(): string
    {
        if (!$this->active) {
            return 'inactive';
        }

        $now = now();

        if ($this->starts_at && $this->starts_at > $now) {
            return 'scheduled';
        }

        if ($this->ends_at && $this->ends_at < $now) {
            return 'expired';
        }

        return 'active';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-success-subtle text-success',
            'scheduled' => 'bg-warning-subtle text-warning',
            'expired' => 'bg-danger-subtle text-danger',
            default => 'bg-secondary-subtle text-secondary',
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'active' => 'bi-check-circle',
            'scheduled' => 'bi-clock',
            'expired' => 'bi-clock-history',
            default => 'bi-slash-circle',
        };
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope a query to only include active banners.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include live banners (active and in schedule).
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

    /**
     * Scope a query to filter by target screen.
     */
    public function scopeForScreen($query, string $screen)
    {
        return $query->where(function ($q) use ($screen) {
            $q->where('target_screen', $screen)
              ->orWhere('target_screen', 'all');
        });
    }

    /**
     * Scope a query to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }
}
