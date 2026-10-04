<?php

namespace App\Models\Credit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CreditApplication extends Model
{
    protected $table = 'credit_applications';

    protected $guarded = ['id'];

    protected $hidden = ['bvn'];

    protected $casts = [
        'bvn'             => 'encrypted',
        'risk_factors'    => 'array',
        'date_of_birth'   => 'date',
        'reviewed_at'     => 'datetime',
        'consented_at'    => 'datetime',
        'requested_limit' => 'float',
        'suggested_limit' => 'float',
        'approved_limit'  => 'float',
    ];

    public const INCOME_BANDS = [
        'below_100k' => 'Below ₦100,000',
        '100k_250k'  => '₦100,000 – ₦250,000',
        '250k_500k'  => '₦250,000 – ₦500,000',
        '500k_1m'    => '₦500,000 – ₦1,000,000',
        'above_1m'   => 'Above ₦1,000,000',
    ];

    public const EMPLOYMENT = [
        'employed'       => 'Employed (salary)',
        'self_employed'  => 'Self-employed',
        'business_owner' => 'Business owner',
        'student'        => 'Student',
        'other'          => 'Other',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function account()
    {
        return $this->hasOne(CreditAccount::class, 'application_id');
    }

    public function incomeLabel(): string
    {
        return self::INCOME_BANDS[$this->monthly_income] ?? $this->monthly_income;
    }

    public function employmentLabel(): string
    {
        return self::EMPLOYMENT[$this->employment_status] ?? $this->employment_status;
    }
}
