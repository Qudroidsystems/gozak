<?php

namespace App\Models\Credit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreditCollectionAttempt extends Model
{
    protected $table = 'credit_collection_attempts';

    protected $guarded = ['id'];

    protected $casts = [
        'amount'           => 'float',
        'gateway_response' => 'array',
        'completed_at'     => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(CreditAccount::class, 'credit_account_id');
    }

    public function statement()
    {
        return $this->belongsTo(CreditStatement::class, 'statement_id');
    }

    public function mandate()
    {
        return $this->belongsTo(CreditMandate::class, 'mandate_id');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'success'    => 'success',
            'failed'     => 'danger',
            'processing' => 'info',
            default      => 'secondary',
        };
    }
}
