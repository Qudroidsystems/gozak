<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View store setting|Update store setting');
    }

    /**
     * Display the store settings (index view)
     */
    public function index()
    {
        $setting = StoreSetting::firstOrFail();

        $pagetitle = 'Store Settings'; // This fixes the error

        return view('settings.store.index', compact('setting', 'pagetitle'));
    }

    /**
     * Show the form for editing the store settings
     */
    public function edit()
    {
        $setting = StoreSetting::firstOrFail();
        return view('settings.store.edit', compact('setting'));
    }

    /**
     * Update the store settings
     */
    public function update(Request $request)
    {
        $this->middleware('permission:Update store setting');

        $request->validate([
            'store_name'      => 'required|string|max:255',
            'currency_symbol' => 'required|string|max:10',
            'currency_code'   => 'required|string|size:3',
            'motto'           => 'nullable|string|max:255',
            'address'         => 'nullable|string',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'website'         => 'nullable|url|max:255',
            'tax_id'          => 'nullable|string|max:100',
            'footer_note'     => 'nullable|string',
            'logo'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $setting = StoreSetting::firstOrFail();

        $data = $request->only([
            'store_name', 'currency_symbol', 'currency_code', 'motto', 'address',
            'phone', 'email', 'website', 'tax_id', 'footer_note'
        ]);

        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        }

        $setting->update($data);

        // Clear cache
        cache()->forget('store_settings');

        return redirect()->route('settings.store.index')->with('success', 'Store settings updated successfully!');
    }
}
