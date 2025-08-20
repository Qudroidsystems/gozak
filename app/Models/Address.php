<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'street',
        'city',
        'state',
        'country',
        'postal_code',
        'phone_number',
        'name',
        'is_default', // Added is_default
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}