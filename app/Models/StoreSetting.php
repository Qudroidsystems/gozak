<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_name',
        'currency_symbol',
        'currency_code',
        'motto',
        'address',
        'phone',
        'email',
        'website',
        'tax_id',
        'footer_note',
        'logo',
    ];

    public static function getSettings()
    {
        return cache()->remember('store_settings', 3600, function () {
            return self::first();
        });
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getCurrencyAttribute()
    {
        return $this->currency_symbol ?? '$';
    }
}
