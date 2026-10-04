@extends('layouts.master')

@section('title', 'Gozak Credit settings')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('credit._nav', ['title' => 'Gozak Credit settings', 'subtitle' => 'Who can use it, what it costs, when bills go out and how money is collected.'])

    <form method="POST" action="{{ route('admin.credit.settings.save') }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-xl-6">
                <x-cb.card title="Availability" icon="ri-toggle-line">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="1" @checked(old('enabled', $s->enabled))>
                        <label class="form-check-label fw-semibold" for="enabled">Gozak Credit is on</label>
                    </div>
                    <div class="alert alert-warning small">
                        Buy-now-pay-later in Nigeria falls under the FCCPC Digital, Electronic, Online or Non-Traditional Consumer Lending Regulations 2025.
                        Keep this off — or limited to pilot users — until your registration is approved. This is not legal advice; confirm with your lawyer.
                    </div>
                    <label class="form-label fw-semibold">Who can apply</label>
                    <select class="form-select mb-2" name="audience">
                        <option value="pilot" @selected(old('audience', $s->audience) === 'pilot')>Pilot only — staff + the emails below</option>
                        <option value="everyone" @selected(old('audience', $s->audience) === 'everyone')>Every signed-in customer</option>
                    </select>
                    <label class="form-label">Pilot customer emails <span class="text-muted small">(one per line)</span></label>
                    <textarea class="form-control font-monospace small" rows="4" name="pilot_emails" placeholder="ada@example.com">{{ old('pilot_emails', $s->pilot_emails) }}</textarea>
                </x-cb.card>

                <x-cb.card title="Pricing & limits" icon="ri-price-tag-3-line" class="mt-3">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Credit fee</label>
                            <div class="input-group"><input type="number" step="0.25" min="0" max="50" class="form-control" name="markup_percent" value="{{ old('markup_percent', $s->markup_percent) }}"><span class="input-group-text">%</span></div>
                            <div class="form-text">Added to every purchase paid with credit.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lowest limit</label>
                            <div class="input-group"><span class="input-group-text">₦</span><input type="number" step="1000" min="0" class="form-control" name="min_limit" value="{{ old('min_limit', (int) $s->min_limit) }}"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Highest limit</label>
                            <div class="input-group"><span class="input-group-text">₦</span><input type="number" step="1000" min="0" class="form-control" name="max_limit" value="{{ old('max_limit', (int) $s->max_limit) }}"></div>
                        </div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Example: a ₦50,000 order costs ₦{{ number_format(50000 * (1 + $s->markup_percent / 100)) }} on credit at {{ $s->markup_percent }}%.</p>
                </x-cb.card>

                <x-cb.card title="Terms shown to customers" icon="ri-file-text-line" class="mt-3">
                    <label class="form-label">Terms version</label>
                    <input class="form-control mb-2" name="terms_version" value="{{ old('terms_version', $s->terms_version) }}">
                    <div class="form-text mb-2">Change the version whenever you change the text; new applications record which version they accepted.</div>
                    <textarea class="form-control small" rows="10" name="terms_text" placeholder="Key facts: credit fee, statement date, debit date, late fees, how to pay early, complaints contact…">{{ old('terms_text', $s->terms_text) }}</textarea>
                </x-cb.card>
            </div>

            <div class="col-xl-6">
                <x-cb.card title="Billing calendar" icon="ri-calendar-2-line">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Statement day</label>
                            <input type="number" min="1" max="28" class="form-control" name="statement_day" value="{{ old('statement_day', $s->statement_day) }}">
                            <div class="form-text">The month's spending is totalled on this day.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Debit (due) day</label>
                            <input type="number" min="1" max="28" class="form-control" name="due_day" value="{{ old('due_day', $s->due_day) }}">
                            <div class="form-text">Money is debited automatically on this day.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reminders (days before due)</label>
                            <input class="form-control" name="reminder_days" value="{{ old('reminder_days', $s->reminder_days) }}" placeholder="5,2,0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Suspend new purchases after</label>
                            <div class="input-group"><input type="number" min="0" max="60" class="form-control" name="suspend_after_days" value="{{ old('suspend_after_days', $s->suspend_after_days) }}"><span class="input-group-text">days overdue</span></div>
                        </div>
                    </div>
                    <div class="alert alert-info small mt-3 mb-0">
                        Next statement: <b>{{ $nextStatement->format('D j M Y') }}</b> · next automatic debit: <b>{{ $nextDue->format('D j M Y') }}</b>.
                        If the debit day is on or before the statement day, the debit happens the following month.
                    </div>
                </x-cb.card>

                <x-cb.card title="Automatic collection" icon="ri-exchange-funds-line" class="mt-3">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="require_mandate" name="require_mandate" value="1" @checked(old('require_mandate', $s->require_mandate))>
                        <label class="form-check-label" for="require_mandate">Customers must link a <b>bank account mandate</b> before they can spend</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="card_fallback" name="card_fallback" value="1" @checked(old('card_fallback', $s->card_fallback))>
                        <label class="form-check-label" for="card_fallback">If a bank debit fails, retry on the customer's saved <b>card</b></label>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Debit attempts per statement</label>
                            <input type="number" min="1" max="20" class="form-control" name="max_attempts" value="{{ old('max_attempts', $s->max_attempts) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Wait between attempts</label>
                            <div class="input-group"><input type="number" min="1" max="168" class="form-control" name="retry_hours" value="{{ old('retry_hours', $s->retry_hours) }}"><span class="input-group-text">hours</span></div>
                        </div>
                    </div>
                    <div class="form-text">Debits only run between 8am and 8pm (Lagos time).</div>
                </x-cb.card>

                <x-cb.card title="Late payment" icon="ri-alarm-warning-line" class="mt-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Grace period</label>
                            <div class="input-group"><input type="number" min="0" max="30" class="form-control" name="grace_days" value="{{ old('grace_days', $s->grace_days) }}"><span class="input-group-text">days</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Flat late fee</label>
                            <div class="input-group"><span class="input-group-text">₦</span><input type="number" min="0" step="100" class="form-control" name="late_fee_flat" value="{{ old('late_fee_flat', (int) $s->late_fee_flat) }}"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">…or % of overdue amount</label>
                            <div class="input-group"><input type="number" min="0" max="20" step="0.25" class="form-control" name="late_fee_percent" value="{{ old('late_fee_percent', $s->late_fee_percent) }}"><span class="input-group-text">%</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Late fee cap</label>
                            <div class="input-group"><span class="input-group-text">₦</span><input type="number" min="0" step="500" class="form-control" name="late_fee_cap" value="{{ old('late_fee_cap', (int) $s->late_fee_cap) }}"></div>
                        </div>
                    </div>
                    <div class="form-text">One late fee per statement: the higher of the flat fee and the percentage, never above the cap. Admins can waive it.</div>
                </x-cb.card>

                <x-cb.card title="Automation status" icon="ri-robot-2-line" class="mt-3">
                    <p class="small mb-2">The billing cycle runs every hour (<code>php artisan credit:run</code> via the scheduler — make sure the cPanel cron for <code>schedule:run</code> is active).</p>
                    @if($lastRun)
                        <div class="small">Last manual run: <b>{{ $lastRun['at'] }}</b> by {{ $lastRun['by'] }}</div>
                    @endif
                    @if($cron = cache('credit:last_cron'))
                        <div class="small">Last scheduled run: <b>{{ $cron }}</b></div>
                    @else
                        <div class="small text-danger">No scheduled run recorded yet — check the cron job.</div>
                    @endif
                </x-cb.card>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3 mb-4">
            <a class="btn btn-light" href="{{ route('admin.credit.dashboard') }}">Cancel</a>
            <button class="btn btn-primary"><i class="ri-save-3-line"></i> Save settings</button>
        </div>
    </form>

</div>
</div>
</div>
@endsection
