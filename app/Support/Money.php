<?php

namespace App\Support;

use App\Models\StoreSetting;

/** Currency helpers for server-side tables (uses the store's currency symbol). */
class Money
{
    public static function symbol(): string
    {
        try {
            return StoreSetting::getSettings()?->currency ?? '₦';
        } catch (\Throwable $e) {
            return '₦';
        }
    }

    public static function fmt($amount, int $decimals = 2): string
    {
        return self::symbol() . number_format((float) $amount, $decimals);
    }
}
