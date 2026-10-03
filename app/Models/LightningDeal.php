<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LightningDeal extends Model
{
    protected $fillable = [
        'product_id',
        'discount_percentage',
        'stock_limit',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'is_active'  => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /** Only deals that are active and within their time window */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where(function ($q) {
                         $q->whereNull('starts_at')
                           ->orWhere('starts_at', '<=', now());
                     })
                     ->where(function ($q) {
                         $q->whereNull('ends_at')
                           ->orWhere('ends_at', '>', now());
                     });
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    /** How many items are left in this deal (respects stock_limit) */
    public function getStockLeftAttribute(): int
    {
        $productStock = $this->product?->stock ?? 0;
        if ($this->stock_limit === null) {
            return $productStock;
        }
        return min($this->stock_limit, $productStock);
    }

    /** Whether this deal has expired */
    public function getIsExpiredAttribute(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    // ── Time & stock helpers (used by the app API and order pricing) ───────────
    //
    // Admins type deal times as Lagos wall-clock times and they are stored as
    // they were typed, while the app runs in UTC. These helpers read them as
    // Africa/Lagos so countdowns sent to the app are exact (with a +01:00
    // offset) instead of an hour off.

    public const TZ = 'Africa/Lagos';

    /** Re-read a stored (naive) time as Lagos time. */
    public static function asLagos($value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        $raw = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : (string) $value;
        return Carbon::parse($raw, self::TZ);
    }

    public function startsAtLagos(): ?Carbon { return self::asLagos($this->getRawOriginal('starts_at')); }
    public function endsAtLagos(): ?Carbon   { return self::asLagos($this->getRawOriginal('ends_at')); }

    /** "now" in the same wall-clock format the deal times are stored in. */
    public static function nowStored(): string
    {
        return now(self::TZ)->format('Y-m-d H:i:s');
    }

    /** Running now (active flag + inside its time window). */
    public function scopeLive($query)
    {
        $now = self::nowStored();
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }

    /** Active but not started yet, starting within [$withinHours]. */
    public function scopeUpcoming($query, int $withinHours = 48)
    {
        $now   = now(self::TZ);
        return $query->where('is_active', true)
            ->where('starts_at', '>', $now->format('Y-m-d H:i:s'))
            ->where('starts_at', '<=', $now->copy()->addHours($withinHours)->format('Y-m-d H:i:s'));
    }

    /** Units sold (paid orders) since the deal started. */
    public function soldCount(): int
    {
        $start = $this->startsAtLagos();
        $q = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $this->product_id)
            ->where('orders.payment_status', 'paid');
        if ($start) {
            // orders.created_at is stored in the app timezone (UTC)
            $q->where('orders.created_at', '>=', $start->copy()->setTimezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i:s'));
        }
        return (int) $q->sum('order_items.quantity');
    }

    /** Deal units still available, or null when the deal has no limit. */
    public function remaining(?int $sold = null): ?int
    {
        if ($this->stock_limit === null) {
            return null;
        }
        return max(0, (int) $this->stock_limit - ($sold ?? $this->soldCount()));
    }

    /** The live deal for a product that still has units left, if any. */
    public static function liveDealFor($productId): ?self
    {
        $deal = static::live()->where('product_id', $productId)->orderBy('sort_order')->first();
        if (!$deal || (int) $deal->discount_percentage <= 0) {
            return null;
        }
        $left = $deal->remaining();
        return ($left === null || $left > 0) ? $deal : null;
    }
}
