<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    protected $fillable = [
        'company_name', 'company_address', 'company_phone', 'company_email',
        'tax_name', 'tax_rate', 'logo', 'primary_color', 'currency',
        'currency_symbol', 'language', 'show_qr_code', 'show_barcode', 'invoice_footer'
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'show_qr_code' => 'boolean',
        'show_barcode' => 'boolean',
    ];

    public static function getSettings()
    {
        $settings = self::first();

        if (!$settings) {
            $settings = self::create([
                'company_name' => config('app.name'),
                'company_address' => '123 Business Street, City, Country',
                'company_phone' => '+1 234 567 890',
                'company_email' => 'sales@' . parse_url(config('app.url'), PHP_URL_HOST),
                'logo' => 'img/logo.png',
                'primary_color' => '#0d6efd',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'language' => 'en',
                'tax_name' => 'VAT',
                'tax_rate' => 15.00,
                'show_qr_code' => true,
                'show_barcode' => true,
                'invoice_footer' => 'Thank you for your business!',
            ]);
        }

        return $settings;
    }
}