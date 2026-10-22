<?php

namespace Database\Seeders;

use App\Models\Variant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Starter attribute library for a Nigerian general store.
 * Safe to run more than once: existing attributes/values are kept,
 * missing ones are added.
 *
 *   php artisan db:seed --class=AttributeLibrarySeeder
 */
class AttributeLibrarySeeder extends Seeder
{
    public function run(): void
    {
        $library = [
            ['Colour', 'color', null, [
                ['Black', '#111111'], ['White', '#FFFFFF'], ['Grey', '#9E9E9E'], ['Silver', '#C0C0C0'],
                ['Red', '#E53935'], ['Wine', '#7B1E3A'], ['Pink', '#F48FB1'], ['Orange', '#FB8C00'],
                ['Yellow', '#FDD835'], ['Gold', '#D4AF37'], ['Brown', '#6D4C41'], ['Beige', '#E8D8B9'],
                ['Cream', '#FFFDD0'], ['Green', '#43A047'], ['Army Green', '#4B5320'], ['Mint', '#98FF98'],
                ['Blue', '#1E88E5'], ['Navy Blue', '#1A237E'], ['Sky Blue', '#87CEEB'], ['Purple', '#8E24AA'],
                ['Multicolour', null],
            ]],
            ['Size', 'size', 'Clothing size', [
                ['XS', null], ['S', null], ['M', null], ['L', null], ['XL', null], ['XXL', null], ['3XL', null], ['4XL', null],
            ]],
            ['Shoe Size', 'size', 'EU sizing', array_map(fn ($n) => ["EU $n", null], range(35, 47))],
            ['Trouser Size', 'size', 'Waist in inches', array_map(fn ($n) => ["$n", null], range(28, 44, 2))],
            ['Kids Age', 'button', null, [
                ['0-3 months', null], ['3-6 months', null], ['6-12 months', null], ['1-2 years', null],
                ['2-3 years', null], ['3-4 years', null], ['4-5 years', null], ['5-6 years', null],
                ['7-8 years', null], ['9-10 years', null], ['11-12 years', null],
            ]],
            ['Storage', 'button', null, [
                ['32GB', null], ['64GB', null], ['128GB', null], ['256GB', null], ['512GB', null], ['1TB', null],
            ]],
            ['RAM', 'button', null, [
                ['2GB', null], ['3GB', null], ['4GB', null], ['6GB', null], ['8GB', null], ['12GB', null], ['16GB', null],
            ]],
            ['Weight', 'button', null, [
                ['250g', null], ['500g', null], ['1kg', null], ['2kg', null], ['5kg', null], ['10kg', null], ['25kg', null], ['50kg', null],
            ]],
            ['Volume', 'button', null, [
                ['250ml', null], ['500ml', null], ['750ml', null], ['1L', null], ['1.5L', null], ['2L', null], ['5L', null],
            ]],
            ['Pack Size', 'button', null, [
                ['Single', null], ['Pack of 2', null], ['Pack of 3', null], ['Pack of 6', null], ['Pack of 12', null], ['Carton', null],
            ]],
        ];

        foreach ($library as $i => [$name, $type, $hint, $values]) {
            $variant = Variant::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'display_type' => $type, 'hint' => $hint, 'sort_order' => $i]
            );

            foreach ($values as $j => [$value, $hex]) {
                $variant->values()->firstOrCreate(
                    ['value' => $value],
                    ['color_hex' => $hex, 'sort_order' => $j]
                );
            }
        }
    }
}
