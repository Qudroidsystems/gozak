<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceNumber extends Model
{
    protected $fillable = ['prefix', 'year', 'next_number', 'padding'];

    public static function generate()
    {
        $year = now()->year;

        $counter = self::firstOrCreate(
            ['year' => $year],
            ['prefix' => 'INV', 'next_number' => 1, 'padding' => 4]
        );

        $number = str_pad($counter->next_number, $counter->padding, '0', STR_PAD_LEFT);
        $invoice = "{$counter->prefix}-{$year}-{$number}";

        $counter->increment('next_number');

        return $invoice;
    }
}