<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A refund on an order.
 *   method  = gateway (sent back through Paystack) | manual (recorded only)
 *   status  = pending | processing | processed | failed | rejected
 * Pending/processing refunds already reserve their amount (see Order::refundableAmount()).
 */
class Refund extends Model
{
    public const OPEN_STATUSES = ['pending', 'processing'];

    protected $fillable = [
        'order_id', 'user_id', 'amount', 'reason', 'status', 'processed_at',
        'method', 'gateway', 'transaction_reference', 'gateway_refund_id',
        'manual_reference', 'gateway_response', 'failure_reason',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'processed_at'     => 'datetime',
        'gateway_response' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function getMethodLabelAttribute(): string
    {
        if ($this->method === 'gateway') {
            return 'Refunded via ' . ucfirst($this->gateway ?: 'gateway');
        }
        return match ($this->gateway) {
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank transfer',
            'opay'          => 'OPay dashboard',
            'paystack'      => 'Paystack dashboard',
            default         => 'Manual',
        };
    }
}
