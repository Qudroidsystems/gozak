<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Single-row delivery settings (auto-confirm after N days). */
class OrderSetting extends Model
{
    protected $table = 'order_settings';

    protected $fillable = ['auto_confirm_enabled', 'auto_confirm_days'];

    protected $casts = [
        'auto_confirm_enabled' => 'boolean',
        'auto_confirm_days'    => 'integer',
    ];

    public static function current(): self
    {
        return Cache::remember('order_settings', 600, function () {
            if (!Schema::hasTable('order_settings')) {
                return new self(['auto_confirm_enabled' => true, 'auto_confirm_days' => 5]);
            }
            return self::query()->first() ?? self::create(['auto_confirm_enabled' => true, 'auto_confirm_days' => 5]);
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('order_settings'));
    }
}
