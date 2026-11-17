<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Settings › Store details: the business name, logo, phone, email and address
 * shown on the website footer/header, the Nuxt storefront (GET /api/store-info),
 * invoices and receipts.
 */
class StoreSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View store setting|Update store setting');
    }

    public function index()
    {
        $setting = StoreSetting::first() ?? StoreSetting::create([
            'store_name' => config('app.name', 'Gozak Mart'),
        ]);

        return view('settings.store.index', [
            'setting'   => $setting,
            'canEdit'   => auth()->user()?->can('Update store setting') ?? false,
            'pagetitle' => 'Store details',
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()?->can('Update store setting'), 403);

        $data = $request->validate([
            'store_name'      => 'required|string|max:255',
            'motto'           => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string|max:1000',
            'website'         => 'nullable|url|max:255',
            'tax_id'          => 'nullable|string|max:100',
            'footer_note'     => 'nullable|string|max:1000',
            'currency_symbol' => 'nullable|string|max:10',
            'currency_code'   => 'nullable|string|size:3',
            'logo'            => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'remove_logo'     => 'nullable|boolean',
        ]);

        $setting = StoreSetting::first() ?? new StoreSetting();

        unset($data['logo'], $data['remove_logo']);
        $data['currency_symbol'] = $data['currency_symbol'] ?: '₦';
        $data['currency_code']   = strtoupper($data['currency_code'] ?: 'NGN');

        if ($request->boolean('remove_logo') && $setting->logo) {
            Storage::disk('public')->delete($setting->logo);
            $data['logo'] = null;
        }
        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $setting->fill($data)->save();
        cache()->forget('store_settings');

        return redirect()->route('settings.store.index')->with('success', 'Store details saved. The website, storefront and invoices now show them.');
    }
}
