<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
     use HasFactory;
     protected $table = "globalSettings";

    protected $fillable = [
        'tax_rate',
        'shipping_cost',
        'free_shipping_threshold',
        'app_name',
        'app_logo',
    ];

    protected $casts = [
        'tax_rate' => 'double',
        'shipping_cost' => 'double',
        'free_shipping_threshold' => 'double',
    ];
}