<?php

namespace App\Models\Credit;

use Illuminate\Database\Eloquent\Model;

class CreditMandate extends Model
{
    protected $table = 'credit_mandates';

    protected $guarded = ['id'];

    protected $hidden = ['authorization_code', 'gateway_data'];

    protected $casts = [
        'authorization_code' => 'encrypted',
        'gateway_data'       => 'array',
        'is_primary'         => 'boolean',
        'activated_at'       => 'datetime',
        'revoked_at'         => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(CreditAccount::class, 'credit_account_id');
    }

    public function label(): string
    {
        return $this->type === 'card'
            ? trim(ucfirst((string) $this->card_brand) . ' card •••• ' . $this->last4)
            : trim(($this->bank_name ?: 'Bank') . ' •••• ' . $this->last4);
    }

    public function toApi(): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'status'       => $this->status,
            'label'        => $this->label(),
            'bank_name'    => $this->bank_name,
            'account_name' => $this->account_name,
            'last4'        => $this->last4,
            'card_brand'   => $this->card_brand,
            'expiry'       => $this->expiry,
            'is_primary'   => $this->is_primary,
            'activated_at' => $this->activated_at?->toIso8601String(),
        ];
    }
}
