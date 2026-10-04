<?php

namespace App\Models\Credit;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreditLedgerEntry extends Model
{
    protected $table = 'credit_ledger_entries';

    protected $guarded = ['id'];

    protected $casts = [
        'amount'        => 'float',
        'balance_after' => 'float',
        'meta'          => 'array',
    ];

    public const LABELS = [
        'purchase'   => 'Purchase',
        'fee'        => 'Credit fee',
        'late_fee'   => 'Late fee',
        'payment'    => 'Payment',
        'refund'     => 'Refund',
        'adjustment' => 'Adjustment',
        'reversal'   => 'Reversal',
    ];

    public function account()
    {
        return $this->belongsTo(CreditAccount::class, 'credit_account_id');
    }

    public function statement()
    {
        return $this->belongsTo(CreditStatement::class, 'statement_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signed(): float
    {
        return $this->direction === 'debit' ? $this->amount : -$this->amount;
    }

    public function toApi(): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type,
            'type_label'    => self::LABELS[$this->type] ?? ucfirst($this->type),
            'direction'     => $this->direction,
            'amount'        => $this->amount,
            'balance_after' => $this->balance_after,
            'order_id'      => $this->order_id,
            'description'   => $this->description,
            'statement_id'  => $this->statement_id,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
