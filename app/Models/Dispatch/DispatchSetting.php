<?php

namespace App\Models\Dispatch;

use Illuminate\Database\Eloquent\Model;

class DispatchSetting extends Model
{
    protected $fillable = [
        'require_delivery_code', 'allow_photo_fallback', 'max_code_attempts', 'default_capacity', 'max_stops_per_run',
        'default_promise_hour', 'pack_within_minutes', 'at_risk_minutes', 'loading_stuck_minutes', 'run_overdue_minutes',
    ];

    protected $casts = [
        'require_delivery_code' => 'boolean',
        'allow_photo_fallback'  => 'boolean',
    ];

    private static ?self $cached = null;

    public static function current(): self
    {
        if (static::$cached) {
            return static::$cached;
        }
        try {
            return static::$cached = static::query()->first() ?? new static(static::defaults());
        } catch (\Throwable $e) {
            return new static(static::defaults());
        }
    }

    public static function forget(): void
    {
        static::$cached = null;
    }

    public static function defaults(): array
    {
        return [
            'require_delivery_code' => true, 'allow_photo_fallback' => true, 'max_code_attempts' => 5,
            'default_capacity' => 12, 'max_stops_per_run' => 12, 'default_promise_hour' => 18,
            'pack_within_minutes' => 120, 'at_risk_minutes' => 120, 'loading_stuck_minutes' => 60, 'run_overdue_minutes' => 240,
        ];
    }
}
