@extends('layouts.master')

@section('title', 'Dispatch settings')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    @include('dispatch._nav', ['title' => 'Dispatch settings', 'subtitle' => 'Delivery confirmation rules, how runs are suggested, and when the board raises an alert.', 'back' => route('dispatch.index'), 'backLabel' => 'Dispatch board'])

    <form method="POST" action="{{ route('dispatch.settings.save') }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-lg-4">
                <x-cb.card title="Delivery confirmation" icon="ri-lock-password-line">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="require_delivery_code" value="0">
                        <input class="form-check-input" type="checkbox" name="require_delivery_code" value="1" id="sCode" @checked($settings->require_delivery_code)>
                        <label class="form-check-label" for="sCode"><b>Require a delivery code</b><div class="small text-muted">Each customer gets a 4-digit code in the app when their order leaves. The rider can only complete the stop with that code.</div></label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="allow_photo_fallback" value="0">
                        <input class="form-check-input" type="checkbox" name="allow_photo_fallback" value="1" id="sPhoto" @checked($settings->allow_photo_fallback)>
                        <label class="form-check-label" for="sPhoto"><b>Allow photo instead of code</b><div class="small text-muted">For customers without the app: rider takes a photo and writes who received it. Shows as "Photo" in reports.</div></label>
                    </div>
                    <label class="form-label small mb-1">Wrong code attempts before locking</label>
                    <input type="number" name="max_code_attempts" min="1" max="20" class="form-control form-control-sm" value="{{ $settings->max_code_attempts }}">
                </x-cb.card>
            </div>
            <div class="col-lg-4">
                <x-cb.card title="Suggested runs" icon="ri-magic-line">
                    <label class="form-label small mb-1">Parcels per run (riders without their own capacity)</label>
                    <input type="number" name="default_capacity" min="1" max="500" class="form-control form-control-sm mb-2" value="{{ $settings->default_capacity }}">
                    <label class="form-label small mb-1">Maximum stops per run</label>
                    <input type="number" name="max_stops_per_run" min="1" max="100" class="form-control form-control-sm mb-2" value="{{ $settings->max_stops_per_run }}">
                    <label class="form-label small mb-1">Promised delivery hour when the order has only a date</label>
                    <select name="default_promise_hour" class="form-select form-select-sm">
                        @for($h = 6; $h <= 23; $h++)<option value="{{ $h }}" @selected($settings->default_promise_hour == $h)>{{ \Illuminate\Support\Carbon::createFromTime($h)->format('g A') }}</option>@endfor
                    </select>
                </x-cb.card>
            </div>
            <div class="col-lg-4">
                <x-cb.card title="Alerts" icon="ri-alarm-warning-line">
                    <label class="form-label small mb-1">Order not packed after (minutes)</label>
                    <input type="number" name="pack_within_minutes" min="10" max="2880" class="form-control form-control-sm mb-2" value="{{ $settings->pack_within_minutes }}">
                    <label class="form-label small mb-1">Warn when promised time is within (minutes)</label>
                    <input type="number" name="at_risk_minutes" min="10" max="1440" class="form-control form-control-sm mb-2" value="{{ $settings->at_risk_minutes }}">
                    <label class="form-label small mb-1">Batch not dispatched after (minutes)</label>
                    <input type="number" name="loading_stuck_minutes" min="10" max="1440" class="form-control form-control-sm mb-2" value="{{ $settings->loading_stuck_minutes }}">
                    <label class="form-label small mb-1">Run still out after (minutes)</label>
                    <input type="number" name="run_overdue_minutes" min="30" max="2880" class="form-control form-control-sm" value="{{ $settings->run_overdue_minutes }}">
                </x-cb.card>
            </div>
        </div>
        <button class="btn btn-primary"><i class="ri-save-3-line"></i> Save settings</button>
    </form>

</div>
</div>
</div>
@endsection
