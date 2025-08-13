<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'phone_number_2',
        'home_address',
        'office_address',
        'gender',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
