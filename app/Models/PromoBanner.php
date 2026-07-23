<?php
// app/Models/PromoBanner.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        // ── Temu-style fields ──────────────────────────────────────────
        'display_style',
        'amount_text',
        'masked_user',
        'from_label',
        'type_label',
        'date_label',
        'conditions_text',
        'announcement_text',
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
        $url = asset('storage/' . $this->image_url);
        Log::debug('📢 [PromoBanner] full_image_url: ' . $url);
        return $url;
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeLive($query)
    {
        $now = now();

        Log::debug('📢 [PromoBanner] Scope live called', ['now' => $now]);

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
        Log::debug('📢 [PromoBanner] Scope forScreen called', ['screen' => $screen]);

        return $query->where(function ($q) use ($screen) {
            $q->where('target_screen', $screen)
              ->orWhere('target_screen', 'all');
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }
}
