<?php

namespace App\Models\Credit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CreditSetting extends Model
{
    protected $table = 'credit_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'enabled'          => 'boolean',
        'card_fallback'    => 'boolean',
        'require_mandate'  => 'boolean',
        'markup_percent'   => 'float',
        'min_limit'        => 'float',
        'max_limit'        => 'float',
        'late_fee_flat'    => 'float',
        'late_fee_percent' => 'float',
        'late_fee_cap'     => 'float',
    ];

    public const TZ = 'Africa/Lagos';

    public static function current(): self
    {
        return Cache::remember('credit:settings', 60, fn () => static::query()->firstOrCreate([]));
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('credit:settings'));
    }

    /** @return int[] days before the due date to remind */
    public function reminderDays(): array
    {
        return collect(explode(',', (string) $this->reminder_days))
            ->map(fn ($d) => (int) trim($d))->filter(fn ($d) => $d >= 0)->unique()->sortDesc()->values()->all();
    }

    public function pilotEmails(): array
    {
        return collect(preg_split('/[\s,;]+/', strtolower((string) $this->pilot_emails)))->filter()->values()->all();
    }

    /** Statement closing date of the cycle that contains $date (Lagos). */
    public function statementDateFor(Carbon $date): Carbon
    {
        $d = $date->copy()->setTimezone(self::TZ)->startOfDay();
        $close = $d->copy()->day(min($this->statement_day, $d->daysInMonth));
        if ($d->gt($close)) {
            $next = $d->copy()->startOfMonth()->addMonthNoOverflow();
            $close = $next->day(min($this->statement_day, $next->daysInMonth));
        }
        return $close;
    }

    /** Due date for a statement closed on $statementDate. */
    public function dueDateFor(Carbon $statementDate): Carbon
    {
        $s = $statementDate->copy()->startOfDay();
        $due = $s->copy()->day(min($this->due_day, $s->daysInMonth));
        if ($due->lte($s)) {
            $next = $s->copy()->startOfMonth()->addMonthNoOverflow();
            $due = $next->day(min($this->due_day, $next->daysInMonth));
        }
        return $due;
    }

    public function lateFeeFor(float $overdue): float
    {
        $fee = max($this->late_fee_flat, round($overdue * $this->late_fee_percent / 100, 2));
        return round(min($fee, $this->late_fee_cap > 0 ? $this->late_fee_cap : $fee), 2);
    }
}
