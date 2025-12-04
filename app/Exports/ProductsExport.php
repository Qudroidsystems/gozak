<?php
namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::select('title', 'sku', 'price', 'sale_price', 'stock', 'description', 'brand_id', 'category_id', 'is_featured')->get();
    }

    public function headings(): array
    {
        return ['title', 'sku', 'price', 'sale_price', 'stock', 'description', 'brand_id', 'category_id', 'is_featured'];
    }
}