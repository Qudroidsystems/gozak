<?php

namespace App\Models\Credit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreditAuditLog extends Model
{
    protected $table = 'credit_audit_logs';

    protected $guarded = ['id'];

    protected $casts = ['data' => 'array'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function account()
    {
        return $this->belongsTo(CreditAccount::class, 'credit_account_id');
    }
}
