{{-- Settings › Store details: shown on the website, the gozakmart.ng storefront, invoices and receipts --}}
@extends('layouts.master')
@section('title', 'Store details')

@section('content')
<div class="main-content">
<div class="page-content">
<div class="container-fluid">

    <x-cb.hero title="Store details" icon="ri-store-2-line"
               subtitle="Your business name and contact details, as customers see them on the website, invoices and receipts." />

    @if(session('success'))<div class="cb-banner info"><i class="ri-check-line"></i><div>{{ session('success') }}</div></div>@endif
    @if($errors->any())
        <div class="cb-banner warning"><i class="ri-error-warning-line"></i><div>{{ $errors->first() }}</div></div>
    @endif
    @if(!$setting->phone || !$setting->email || !$setting->address)
        <div class="cb-banner warning"><i class="ri-information-line"></i>
            <div>Customers and Google Play reviewers look for real contact details. Please fill in the <strong>phone</strong>, <strong>email</strong> and <strong>address</strong>.</div>
        </div>
    @endif

    <form action="{{ route('settings.store.update') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
        @csrf @method('PUT')
        <fieldset @disabled(!$canEdit)>

        <x-cb.card title="Business" icon="ri-building-line">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="store_name">Store name <span class="text-danger">*</span></label>
                    <input class="form-control" id="store_name" name="store_name" required maxlength="255" value="{{ old('store_name', $setting->store_name) }}" placeholder="Gozak Mart">
                    <div class="form-text">Shown in the website title, header and invoices.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="motto">Tagline</label>
                    <input class="form-control" id="motto" name="motto" maxlength="255" value="{{ old('motto', $setting->motto) }}" placeholder="Groceries delivered to your door">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="logo">Logo</label>
                    <input class="form-control" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                    <div class="form-text">PNG, JPG, WebP or SVG, up to 2 MB.</div>
                </div>
                <div class="col-md-6 d-flex align-items-center gap-3">
                    @if($setting->logo)
                        <img src="{{ $setting->logo_url }}" alt="Current logo" style="height:56px;max-width:180px;object-fit:contain;background:#f6f6f6;border-radius:8px;padding:4px">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="remove_logo" name="remove_logo">
                            <label class="form-check-label" for="remove_logo">Remove logo</label>
                        </div>
                    @else
                        <span class="text-muted small">No logo yet. The store name is shown instead.</span>
                    @endif
                </div>
            </div>
        </x-cb.card>

        <x-cb.card title="Contact details" icon="ri-contacts-line">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="phone">Phone</label>
                    <input class="form-control" id="phone" name="phone" maxlength="50" value="{{ old('phone', $setting->phone) }}" placeholder="+234 …">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" type="email" id="email" name="email" maxlength="255" value="{{ old('email', $setting->email) }}" placeholder="support@gozakmart.ng">
                </div>
                <div class="col-md-12">
                    <label class="form-label" for="address">Business address</label>
                    <textarea class="form-control" id="address" name="address" rows="2" maxlength="1000" placeholder="Street, area, city, state">{{ old('address', $setting->address) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="website">Website</label>
                    <input class="form-control" type="url" id="website" name="website" maxlength="255" value="{{ old('website', $setting->website) }}" placeholder="https://gozakmart.ng">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tax_id">Tax ID / RC number</label>
                    <input class="form-control" id="tax_id" name="tax_id" maxlength="100" value="{{ old('tax_id', $setting->tax_id) }}">
                </div>
            </div>
        </x-cb.card>

        <x-cb.card title="Invoices & receipts" icon="ri-file-list-3-line">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="footer_note">Footer note</label>
                    <input class="form-control" id="footer_note" name="footer_note" maxlength="1000" value="{{ old('footer_note', $setting->footer_note) }}" placeholder="Thank you for shopping with us!">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="currency_symbol">Currency symbol</label>
                    <input class="form-control" id="currency_symbol" name="currency_symbol" maxlength="10" value="{{ old('currency_symbol', $setting->currency_symbol ?: '₦') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="currency_code">Currency code</label>
                    <input class="form-control text-uppercase" id="currency_code" name="currency_code" maxlength="3" value="{{ old('currency_code', $setting->currency_code ?: 'NGN') }}">
                </div>
            </div>
        </x-cb.card>

        </fieldset>

        @if($canEdit)
            <div class="d-flex justify-content-end mb-4">
                <button type="submit" class="btn btn-primary"><i class="ri-save-line me-1"></i>Save store details</button>
            </div>
        @else
            <p class="text-muted small">You can view these details. Ask an admin for the "Update store setting" permission to change them.</p>
        @endif
    </form>

</div>
</div>
</div>
@endsection
