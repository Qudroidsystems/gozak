<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBankAccount extends Model
{
    public const MAX_PER_USER = 3;

    protected $fillable = [
        'user_id', 'bank_code', 'bank_name', 'account_number', 'account_hash',
        'account_last4', 'account_name', 'name_matches', 'is_default', 'verified_at',
    ];

    protected $hidden = ['account_number', 'account_hash'];

    protected $casts = [
        'account_number' => 'encrypted',
        'name_matches'   => 'boolean',
        'is_default'     => 'boolean',
        'verified_at'    => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function hashNumber(string $bankCode, string $number): string
    {
        return hash_hmac('sha256', $bankCode . '|' . $number, (string) config('app.key'));
    }

    public function maskedNumber(): string
    {
        return '•••• ' . $this->account_last4;
    }

    /** What the app sees (never the full number). */
    public function toApi(): array
    {
        return [
            'id'             => $this->id,
            'bank_code'      => $this->bank_code,
            'bank_name'      => $this->bank_name,
            'account_name'   => $this->account_name,
            'account_last4'  => $this->account_last4,
            'masked_number'  => $this->maskedNumber(),
            'name_matches'   => $this->name_matches,
            'is_default'     => $this->is_default,
            'verified'       => $this->verified_at !== null,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
