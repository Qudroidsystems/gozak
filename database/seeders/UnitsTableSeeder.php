<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitsTableSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Unit::truncate();

        $units = [
            ['name' => 'Piece', 'short_name' => 'pc', 'description' => 'Single item', 'is_default' => true, 'is_active' => true],
            ['name' => 'Pack', 'short_name' => 'pk', 'description' => 'Pack of items', 'is_default' => false, 'is_active' => true],
            ['name' => 'Box', 'short_name' => 'bx', 'description' => 'Box of items', 'is_default' => false, 'is_active' => true],
            ['name' => 'Kilogram', 'short_name' => 'kg', 'description' => 'Weight measurement', 'is_default' => false, 'is_active' => true],
            ['name' => 'Gram', 'short_name' => 'g', 'description' => 'Small weight measurement', 'is_default' => false, 'is_active' => true],
            ['name' => 'Liter', 'short_name' => 'L', 'description' => 'Liquid volume', 'is_default' => false, 'is_active' => true],
            ['name' => 'Milliliter', 'short_name' => 'ml', 'description' => 'Small liquid volume', 'is_default' => false, 'is_active' => true],
            ['name' => 'Meter', 'short_name' => 'm', 'description' => 'Length measurement', 'is_default' => false, 'is_active' => true],
            ['name' => 'Centimeter', 'short_name' => 'cm', 'description' => 'Small length measurement', 'is_default' => false, 'is_active' => true],
            ['name' => 'Dozen', 'short_name' => 'dz', 'description' => '12 pieces', 'is_default' => false, 'is_active' => true],
            ['name' => 'Set', 'short_name' => 'set', 'description' => 'Complete set', 'is_default' => false, 'is_active' => true],
            ['name' => 'Pair', 'short_name' => 'pr', 'description' => 'Two items', 'is_default' => false, 'is_active' => true],
            ['name' => 'Bottle', 'short_name' => 'btl', 'description' => 'Single bottle', 'is_default' => false, 'is_active' => true],
            ['name' => 'Carton', 'short_name' => 'ctn', 'description' => 'Cardboard box', 'is_default' => false, 'is_active' => true],
            ['name' => 'Roll', 'short_name' => 'rl', 'description' => 'Roll of material', 'is_default' => false, 'is_active' => true],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
