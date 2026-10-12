<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Str;

/** Who / where an order goes — one place so labels, slips, manifests and screens agree. */
class DeliveryInfo
{
    public static function address(Order $o)
    {
        return $o->shippingAddress ?? $o->billingAddress;
    }

    public static function recipient(Order $o): string
    {
        $a = self::address($o);
        $customer = $o->user?->full_name ?: $o->user?->name
            ?: trim(($o->customer->first_name ?? '') . ' ' . ($o->customer->last_name ?? ''));
        return trim((string) ($a->name ?? '')) ?: ($customer ?: 'Walk-in customer');
    }

    public static function phone(Order $o): ?string
    {
        $a = self::address($o);
        return $a->phone_number ?? $o->user?->phone_number ?? $o->customer?->phone_number ?? null;
    }

    public static function altPhone(Order $o): ?string
    {
        return self::address($o)->alternate_phone ?? null;
    }

    /** "Ikeja, Lagos" — the part a rider sorts by. */
    public static function area(Order $o): string
    {
        $a = self::address($o);
        if (!$a) {
            return 'PICKUP';
        }
        $place = collect([$a->lga ?? null, $a->city ?? null])->filter()->unique(fn ($v) => mb_strtolower(trim($v)))->implode(', ');
        return trim($place . ($a->state ? ', ' . $a->state : ''), ', ') ?: '—';
    }

    /** Street + landmark, shortened for a small label. */
    public static function shortAddress(Order $o, int $limit = 70): string
    {
        $a = self::address($o);
        if (!$a) {
            return 'Collect at store';
        }
        return Str::limit(trim((string) $a->street), $limit);
    }

    public static function fullAddress(Order $o): string
    {
        $a = self::address($o);
        if (!$a) {
            return 'No delivery address (pickup)';
        }
        return collect([$a->street, $a->city, $a->lga ?? null, $a->state])
            ->filter()
            ->unique(fn ($v) => mb_strtolower(trim($v)))
            ->implode(', ');
    }

    public static function landmark(Order $o): ?string
    {
        return self::address($o)->landmark ?? null;
    }

    /** Amount the rider must collect (unpaid orders only). */
    public static function amountToCollect(Order $o): float
    {
        return $o->payment_status === 'paid' ? 0.0 : (float) $o->total_amount;
    }
}
