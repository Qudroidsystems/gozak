<?php

namespace App\Models\Dispatch;

use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $fillable = ['stock_location_id', 'code', 'name', 'areas', 'color', 'sort_order', 'is_active'];

    protected $casts = [
        'areas'     => 'array',
        'is_active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** Lower-cased keywords this zone answers to (its name + every listed area). */
    public function keywords(): array
    {
        return collect($this->areas ?? [])
            ->push($this->name)
            ->map(fn ($a) => mb_strtolower(trim((string) $a)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function areasText(): string
    {
        return implode(', ', $this->areas ?? []);
    }
}
