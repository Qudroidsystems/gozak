<?php
namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Product([
            'title'       => $row['title'],
            'sku'         => $row['sku'],
            'price'       => $row['price'],
            'sale_price'  => $row['sale_price'] ?? null,
            'stock'       => $row['stock'],
            'description' => $row['description'] ?? null,
            'brand_id'    => $row['brand_id'] ?? null,
            'category_id' => $row['category_id'] ?? null,
            'is_featured' => $row['is_featured'] ?? 0,
        ]);
    }
}