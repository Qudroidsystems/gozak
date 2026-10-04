<?php

namespace App\Models\Credit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreditAccount extends Model
{
    protected $table = 'credit_accounts';

    protected $guarded = ['id'];

    protected $casts = [
        'credit_limit'    => 'float',
        'balance'         => 'float',
        'unbilled'        => 'float',
        'markup_percent'  => 'float',
        'card_expires_on' => 'date',
        'activated_at'    => 'datetime',
        'closed_at'       => 'datetime',
    ];

    public const PENDING_MANDATE = 'pending_mandate';
    public const ACTIVE          = 'active';
    public const SUSPENDED       = 'suspended'; // automatic: overdue
    public const FROZEN          = 'frozen';    // manual: admin
    public const CLOSED          = 'closed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->belongsTo(CreditApplication::class, 'application_id');
    }

    public function mandates()
    {
        return $this->hasMany(CreditMandate::class);
    }

    public function activeMandates()
    {
        return $this->mandates()->where('status', 'active');
    }

    public function statements()
    {
        return $this->hasMany(CreditStatement::class)->latest('period_end');
    }

    public function ledger()
    {
        return $this->hasMany(CreditLedgerEntry::class)->latest('id');
    }

    public function attempts()
    {
        return $this->hasMany(CreditCollectionAttempt::class)->latest('id');
    }

    public function audit()
    {
        return $this->hasMany(CreditAuditLog::class)->latest('id');
    }

    public function available(): float
    {
        return max(0, round($this->credit_limit - $this->balance, 2));
    }

    public function markup(): float
    {
        return $this->markup_percent ?? CreditSetting::current()->markup_percent;
    }

    public function canSpend(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PENDING_MANDATE => 'Awaiting bank link',
            self::ACTIVE          => 'Active',
            self::SUSPENDED       => 'Suspended (overdue)',
            self::FROZEN          => 'Frozen',
            self::CLOSED          => 'Closed',
            default               => ucfirst($this->status),
        };
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            self::ACTIVE          => 'success',
            self::PENDING_MANDATE => 'info',
            self::SUSPENDED       => 'danger',
            self::FROZEN          => 'warning',
            default               => 'secondary',
        };
    }

    public function maskedCard(): string
    {
        return '•••• •••• •••• ' . substr($this->card_number, -4);
    }
}
