<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Model;

class CreditStatement extends Model
{
    protected $table = 'credit_statements';

    protected $guarded = ['id'];

    protected $casts = [
        'period_start'        => 'date',
        'period_end'          => 'date',
        'due_date'            => 'date',
        'paid_at'             => 'datetime',
        'late_fee_applied_at' => 'datetime',
        'next_attempt_at'     => 'datetime',
        'opening_balance'     => 'float',
        'purchases'           => 'float',
        'fees'                => 'float',
        'payments'            => 'float',
        'credits'             => 'float',
        'amount_due'          => 'float',
        'amount_paid'         => 'float',
    ];

    public const OPEN = ['issued', 'partially_paid', 'overdue'];

    public function account()
    {
        return $this->belongsTo(CreditAccount::class, 'credit_account_id');
    }

    public function entries()
    {
        return $this->hasMany(CreditLedgerEntry::class, 'statement_id')->orderBy('id');
    }

    public function collectionAttempts()
    {
        return $this->hasMany(CreditCollectionAttempt::class, 'statement_id')->latest('id');
    }

    public function outstanding(): float
    {
        return max(0, round($this->amount_due - $this->amount_paid, 2));
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'paid'           => 'success',
            'partially_paid' => 'warning',
            'overdue'        => 'danger',
            'waived'         => 'secondary',
            default          => 'info',
        };
    }

    public function toApi(): array
    {
        return [
            'id'              => $this->id,
            'number'          => $this->number,
            'period_start'    => $this->period_start?->toDateString(),
            'period_end'      => $this->period_end?->toDateString(),
            'opening_balance' => $this->opening_balance,
            'purchases'       => $this->purchases,
            'fees'            => $this->fees,
            'payments'        => $this->payments,
            'credits'         => $this->credits,
            'amount_due'      => $this->amount_due,
            'amount_paid'     => $this->amount_paid,
            'outstanding'     => $this->outstanding(),
            'due_date'        => $this->due_date?->toDateString(),
            'status'          => $this->status,
            'paid_at'         => $this->paid_at?->toIso8601String(),
        ];
    }
}
