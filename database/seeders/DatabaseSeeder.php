<?php

namespace Database\Seeders;

use App\Models\Banner;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


         // Disable foreign key checks to avoid constraint issues during seeding
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Truncate tables to ensure a clean slate
        Category::truncate();
        Brand::truncate();
        Product::truncate();
        ProductAttribute::truncate();
        ProductVariation::truncate();
        ProductImage::truncate();
        DB::table('product_category')->truncate();
        DB::table('product_brand')->truncate();
        Banner::truncate();

        // Categories
        $categories = [
            ['id' => 1, 'name' => 'Sports', 'image' => 'category/icons8-bowling-64.png', 'is_featured' => true],
            ['id' => 5, 'name' => 'Furniture', 'image' => 'category/icons8-dining-chair-64.png', 'is_featured' => true],
            ['id' => 2, 'name' => 'Electronics', 'image' => 'category/icons8-smartphone-64.png', 'is_featured' => true],
            ['id' => 3, 'name' => 'Clothes', 'image' => 'category/icons8-tailors-dummy-64.png', 'is_featured' => true],
            ['id' => 4, 'name' => 'Animals', 'image' => 'category/icons8-dog-heart-64.png', 'is_featured' => true],
            ['id' => 6, 'name' => 'Shoes', 'image' => 'category/icons8-shoes-64.png', 'is_featured' => true],
            ['id' => 7, 'name' => 'Cosmetics', 'image' => 'category/icons8-cosmetics-64.png', 'is_featured' => true],
            ['id' => 14, 'name' => 'Jewelery', 'image' => 'category/icons8-sparkling-diamond-64.png', 'is_featured' => true],
            // Subcategories
            ['id' => 8, 'name' => 'Sport Shoes', 'image' => 'category/icons8-bowling-64.png', 'parent_id' => 1, 'is_featured' => false],
            ['id' => 9, 'name' => 'Track suits', 'image' => 'category/icons8-bowling-64.png', 'parent_id' => 1, 'is_featured' => false],
            ['id' => 10, 'name' => 'Sports Equipments', 'image' => 'category/icons8-bowling-64.png', 'parent_id' => 1, 'is_featured' => false],
            ['id' => 11, 'name' => 'Bedroom furniture', 'image' => 'category/icons8-dining-chair-64.png', 'parent_id' => 5, 'is_featured' => false],
            ['id' => 12, 'name' => 'Kitchen furniture', 'image' => 'category/icons8-dining-chair-64.png', 'parent_id' => 5, 'is_featured' => false],
            ['id' => 13, 'name' => 'Office furniture', 'image' => 'category/icons8-dining-chair-64.png', 'parent_id' => 5, 'is_featured' => false],
            ['id' => 15, 'name' => 'Mobile', 'image' => 'category/icons8-smartphone-64.png', 'parent_id' => 2, 'is_featured' => false],
            ['id' => 16, 'name' => 'Shirts', 'image' => 'category/icons8-tailors-dummy-64.png', 'parent_id' => 3, 'is_featured' => false],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // Brands
        $brands = [
            ['id' => 1, 'name' => 'Nike', 'logo' => 'brand/nike.png'],
            ['id' => 2, 'name' => 'Adidas', 'logo' => 'brand/adidas-logo.png'],
            ['id' => 8, 'name' => 'Kenwood', 'logo' => 'brand/kenwood-logo.png'],
            ['id' => 9, 'name' => 'IKEA', 'logo' => 'brand/ikea_logo.png'],
            ['id' => 5, 'name' => 'Apple', 'logo' => 'brand/apple-logo.png'],
            ['id' => 10, 'name' => 'Acer', 'logo' => 'brand/acer_logo.png'],
            ['id' => 3, 'name' => 'Jordan', 'logo' => 'brand/jordan-logo.png'],
            ['id' => 4, 'name' => 'Puma', 'logo' => 'brand/puma-logo.png'],
            ['id' => 6, 'name' => 'ZARA', 'logo' => 'brand/zara-logo.png'],
            ['id' => 7, 'name' => 'Samsung', 'logo' => 'brand/kenwood-logo.png'],
        ];

        foreach ($brands as $brand) {
            Brand::create($brand);
        }

        // Products
        $products = [
            [
                'id' => '001', 'title' => 'Green Nike sports shoe', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 135.00, 'sale_price' => 30.00, 
                'thumbnail' => 'product/nike-shoes.png', 'description' => 'Green Nike sports shoe', 'product_type' => 'variable', 
                'is_featured' => true, 'category_id' => 1, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '002', 'title' => 'Blue T-shirt for all ages', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 35.00, 'sale_price' => 30.00, 
                'thumbnail' => 'product/tshirt_blue_without_collar_front.png', 
                'description' => 'This is a Product description for Blue Nike Sleeve less vest.', 
                'product_type' => 'single', 'is_featured' => true, 'category_id' => 16, 'brand_id' => 6, 'sold_quantity' => 0
            ],
            [
                'id' => '003', 'title' => 'Leather brown Jacket', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 38000.00, 'sale_price' => 30.00, 
                'thumbnail' => 'product/leather_jacket_1.png', 
                'description' => 'This is a Product description for Leather brown Jacket.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 16, 'brand_id' => 6, 'sold_quantity' => 0
            ],
            [
                'id' => '004', 'title' => '4 Color collar t-shirt dry fit', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 135.00, 'sale_price' => 30.00, 
                'thumbnail' => 'product/tshirt_red_collar.png', 
                'description' => 'This is a Product description for 4 Color collar t-shirt dry fit.', 
                'product_type' => 'variable', 'is_featured' => false, 'category_id' => 16, 'brand_id' => 6, 'sold_quantity' => 0
            ],
            [
                'id' => '005', 'title' => 'Nike Air Jordon Shoes', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 35.00, 'sale_price' => 30.00, 
                'thumbnail' => 'product/NikeAirJOrdonWhiteRed.png', 
                'description' => 'Nike Air Jordon Shoes for running. Quality product, Long Lasting', 
                'product_type' => 'variable', 'is_featured' => false, 'category_id' => 8, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '006', 'title' => 'SAMSUNG Galaxy S9 (Pink, 64 GB)  (4 GB RAM)', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 750.00, 'sale_price' => 650.00, 
                'thumbnail' => 'product/samsung_s9_mobile.png', 
                'description' => 'SAMSUNG Galaxy S9 (Pink, 64 GB)  (4 GB RAM), Long Battery timing', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 2, 'brand_id' => 7, 'sold_quantity' => 0
            ],
            [
                'id' => '007', 'title' => 'TOMI Dog food', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 20.00, 'sale_price' => 10.00, 
                'thumbnail' => 'product/tomi_dogfood.png', 
                'description' => 'This is a Product description for TOMI Dog food.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 4, 'brand_id' => 7, 'sold_quantity' => 0
            ],
            [
                'id' => '008', 'title' => 'APPLE iPhone 8 (Black, 64 GB)', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 480.00, 'sale_price' => 380.00, 
                'thumbnail' => 'product/iphone8_mobile.png', 
                'description' => 'This is a Product description for iphone 8.', 
                'product_type' => 'single', 'is_featured' => true, 'category_id' => 4, 'brand_id' => 5, 'sold_quantity' => 0
            ],
            [
                'id' => '009', 'title' => 'Nike Air Jordon 19 Blue', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 400.00, 'sale_price' => 200.00, 
                'thumbnail' => 'product/NikeAirJordonSingleBlue.png', 
                'description' => 'This is a Product description for Nike Air Jordon.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 8, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '010', 'title' => 'Nike Air Jordon 6 Orange', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 400.00, 
                'thumbnail' => 'product/NikeAirJordonSingleOrange.png', 
                'description' => 'This is a Product description for Nike Air Jordon.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 8, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '011', 'title' => 'Nike Air Max Red & Black', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 600.00, 'sale_price' => 400.00, 
                'thumbnail' => 'product/NikeAirMax.png', 
                'description' => 'This is a Product description for Nike Air Max.', 
                'product_type' => 'single', 'is_featured' => true, 'category_id' => 8, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '012', 'title' => 'Nike Basketball shoes Black & Green', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 600.00, 'sale_price' => 400.00, 
                'thumbnail' => 'product/NikeBasketballShoeGreenBlack.png', 
                'description' => 'This is a Product description for Nike Basketball shoes.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 8, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '013', 'title' => 'Nike wild horse shoes', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 600.00, 'sale_price' => 400.00, 
                'thumbnail' => 'product/NikeWildhorse.png', 
                'description' => 'This is a Product description for Nike wild horse shoes.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 8, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '014', 'title' => 'Nike Track suit red', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 500.00, 
                'thumbnail' => 'product/tracksuit_red.png', 
                'description' => 'This is a Product description for Nike Track suit red.', 
                'product_type' => 'single', 'is_featured' => true, 'category_id' => 9, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '015', 'title' => 'Nike Track suit Black', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 200.00, 
                'thumbnail' => 'product/tracksuit_black.png', 
                'description' => 'This is a Product description for Nike Track suit Black.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 9, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '016', 'title' => 'Nike Track suit Blue', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 100.00, 
                'thumbnail' => 'product/tracksuit_blue.png', 
                'description' => 'This is a Product description for Nike Track suit Blue.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 9, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '017', 'title' => 'Nike Track suit Parrot Green', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 350.00, 
                'thumbnail' => 'product/trcksuit_parrotgreen.png', 
                'description' => 'This is a Product description for Nike Track suit Parrot Green.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 9, 'brand_id' => 1, 'sold_quantity' => 0
            ],
            [
                'id' => '018', 'title' => 'Adidas Football', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 40.00, 
                'thumbnail' => 'product/Adidas_Football.png', 
                'description' => 'This is a Product description for Football.', 
                'product_type' => 'single', 'is_featured' => true, 'category_id' => 10, 'brand_id' => 2, 'sold_quantity' => 0
            ],
            [
                'id' => '019', 'title' => 'Baseball Bat', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 30.00, 
                'thumbnail' => 'product/baseball_bat.png', 
                'description' => 'This is a Product description for Baseball Bat.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 10, 'brand_id' => 2, 'sold_quantity' => 0
            ],
            [
                'id' => '020', 'title' => 'Cricket Bat', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 25.00, 
                'thumbnail' => 'product/cricket_bat.png', 
                'description' => 'This is a Product description for Cricket Bat.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 10, 'brand_id' => 2, 'sold_quantity' => 0
            ],
            [
                'id' => '021', 'title' => 'Tennis Racket', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 54.00, 
                'thumbnail' => 'product/tennis_racket.png', 
                'description' => 'This is a Product description for Tennis Racket.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 10, 'brand_id' => 2, 'sold_quantity' => 0
            ],
            [
                'id' => '022', 'title' => 'Pure Wooden Bed', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 950.00, 'sale_price' => 600.00, 
                'thumbnail' => 'product/bedroom_bed.png', 
                'description' => 'Flutter is Google’s mobile UI open source framework to build high-quality native (super fast) interfaces for iOS and Android apps with the unified codebase.', 
                'product_type' => 'variable', 'is_featured' => true, 'category_id' => 11, 'brand_id' => 8, 'sold_quantity' => 0
            ],
            [
                'id' => '023', 'title' => 'Side Table Lamp', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 25.00, 
                'thumbnail' => 'product/bedroom_lamp.png', 
                'description' => 'This is a Product description for Side Table Lamp.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 11, 'brand_id' => 8, 'sold_quantity' => 0
            ],
            [
                'id' => '024', 'title' => 'Bedroom Sofa', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 25.00, 
                'thumbnail' => 'product/bedroom_sofa.png', 
                'description' => 'This is a Product description for Bedroom Sofa.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 11, 'brand_id' => 8, 'sold_quantity' => 0
            ],
            [
                'id' => '025', 'title' => 'Wardrobe for Bedroom', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 56.00, 
                'thumbnail' => 'product/bedroom_wardrobe.png', 
                'description' => 'This is a Product description for Bedroom Wardrobe.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 11, 'brand_id' => 8, 'sold_quantity' => 0
            ],
            [
                'id' => '026', 'title' => 'Kitchen Counter', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 1012.00, 
                'thumbnail' => 'product/kitchen_counter.png', 
                'description' => 'This is a Product description for Kitchen Counter.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 12, 'brand_id' => 2, 'sold_quantity' => 0
            ],
            [
                'id' => '027', 'title' => 'Dinning Table', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 1012.00, 
                'thumbnail' => 'product/kitchen_dining table.png', 
                'description' => 'This is a Product description for Dinning Table.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 12, 'brand_id' => 2, 'sold_quantity' => 0
            ],
            [
                'id' => '028', 'title' => 'Refrigerator', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 987.00, 
                'thumbnail' => 'product/kitchen_refrigerator.png', 
                'description' => 'This is a Product description for Refrigerator.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 12, 'brand_id' => 2, 'sold_quantity' => 0
            ],
            [
                'id' => '029', 'title' => 'Office Chair Red', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 150.00, 
                'thumbnail' => 'product/office_chair_1.png', 
                'description' => 'This is a Product description for Office Chair.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 13, 'brand_id' => 9, 'sold_quantity' => 0
            ],
            [
                'id' => '030', 'title' => 'Office Chair White', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 140.00, 
                'thumbnail' => 'product/office_chair_2.png', 
                'description' => 'This is a Product description for Office Chair.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 13, 'brand_id' => 9, 'sold_quantity' => 0
            ],
            [
                'id' => '031', 'title' => 'Office Desk Red', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 360.00, 
                'thumbnail' => 'product/office_desk_1.png', 
                'description' => 'This is a Product description for Office Desk.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 13, 'brand_id' => 9, 'sold_quantity' => 0
            ],
            [
                'id' => '032', 'title' => 'Office Desk brown', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 400.00, 
                'thumbnail' => 'product/office_desk_2.png', 
                'description' => 'This is a Product description for Office Desk.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 13, 'brand_id' => 9, 'sold_quantity' => 0
            ],
            [
                'id' => '033', 'title' => 'Acer Laptop RAM 8gb to 16gb 512gb to 2tb', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 950.00, 'sale_price' => 800.00, 
                'thumbnail' => 'product/acer_laptop_var_1.png', 
                'description' => 'This is a Product description for Acer Laptop.', 
                'product_type' => 'variable', 'is_featured' => true, 'category_id' => 14, 'brand_id' => 10, 'sold_quantity' => 0
            ],
            [
                'id' => '034', 'title' => 'Acer Laptop 6gb 1tb', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 400.00, 
                'thumbnail' => 'product/acer_laptop_2.png', 
                'description' => 'This is a Product description for Acer Laptop.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 14, 'brand_id' => 10, 'sold_quantity' => 0
            ],
            [
                'id' => '035', 'title' => 'Acer Laptop 6gb 500Gb', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 400.00, 
                'thumbnail' => 'product/acer_laptop_3.png', 
                'description' => 'This is a Product description for Acer Laptop.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 14, 'brand_id' => 10, 'sold_quantity' => 0
            ],
            [
                'id' => '036', 'title' => 'Acer Laptop 4gb 500Gb', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 400.00, 
                'thumbnail' => 'product/acer_laptop_4.png', 
                'description' => 'This is a Product description for Acer Laptop.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 14, 'brand_id' => 10, 'sold_quantity' => 0
            ],
            [
                'id' => '037', 'title' => 'Iphone 13 pro 512gb', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 999.00, 
                'thumbnail' => 'product/iphone_13_pro.png', 
                'description' => 'This is a Product description for Iphone.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 15, 'brand_id' => 5, 'sold_quantity' => 0
            ],
            [
                'id' => '038', 'title' => 'Iphone 14 pro 512gb', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 999.00, 
                'thumbnail' => 'product/iphone_14_pro.png', 
                'description' => 'This is a Product description for Iphone.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 15, 'brand_id' => 5, 'sold_quantity' => 0
            ],
            [
                'id' => '039', 'title' => 'Iphone 14 white 512gb', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 999.00, 
                'thumbnail' => 'product/iphone_14_white.png', 
                'description' => 'This is a Product description for Iphone.', 
                'product_type' => 'single', 'is_featured' => false, 'category_id' => 15, 'brand_id' => 5, 'sold_quantity' => 0
            ],
            [
                'id' => '040', 'title' => 'Iphone 12, 4 Colors 128gb and 256gb', 'sku' => 'ABR4568', 'stock' => 15, 'price' => 950.00, 'sale_price' => 800.00, 
                'thumbnail' => 'product/iphone_12_red.png', 
                'description' => 'This is a Product description for Iphone 12.', 
                'product_type' => 'variable', 'is_featured' => true, 'category_id' => 15, 'brand_id' => 5, 'sold_quantity' => 0
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Product Attributes
        $productAttributes = [
            ['product_id' => '001', 'name' => 'Color', 'values' => json_encode(['Green', 'Black', 'Red'])],
            ['product_id' => '001', 'name' => 'Size', 'values' => json_encode(['EU 30', 'EU 32', 'EU 34'])],
            ['product_id' => '002', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '002', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '003', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '003', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '004', 'name' => 'Color', 'values' => json_encode(['Red', 'Yellow', 'Green', 'Blue'])],
            ['product_id' => '004', 'name' => 'Size', 'values' => json_encode(['EU 30', 'EU 32', 'EU 34'])],
            ['product_id' => '005', 'name' => 'Color', 'values' => json_encode(['Orange', 'Black', 'Brown'])],
            ['product_id' => '005', 'name' => 'Size', 'values' => json_encode(['EU 30', 'EU 32', 'EU 34'])],
            ['product_id' => '006', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '006', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '007', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '007', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '008', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '008', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '009', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '009', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '010', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '010', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '011', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '011', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '012', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '012', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '013', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '013', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '014', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '014', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '015', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '015', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '016', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '016', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '017', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '017', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '018', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '018', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '019', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '019', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '020', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '020', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '021', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '021', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '022', 'name' => 'Color', 'values' => json_encode(['Black', 'Grey', 'Brown'])],
            ['product_id' => '022', 'name' => 'Size', 'values' => json_encode(['EU 30', 'EU 32', 'EU 34'])],
            ['product_id' => '023', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '023', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '024', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '024', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '025', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '025', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '026', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '026', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '027', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '027', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '028', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '028', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '029', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '029', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '030', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '030', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '031', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '031', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '032', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '032', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '033', 'name' => 'Ram', 'values' => json_encode(['6', '8', '16'])],
            ['product_id' => '033', 'name' => 'SSD', 'values' => json_encode(['512', '1 tb', '2 tb'])],
            ['product_id' => '034', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '034', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '035', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '035', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '036', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '036', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '037', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '037', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '038', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '038', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '039', 'name' => 'Size', 'values' => json_encode(['EU34', 'EU32'])],
            ['product_id' => '039', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue'])],
            ['product_id' => '040', 'name' => 'Color', 'values' => json_encode(['Green', 'Red', 'Blue', 'Black'])],
            ['product_id' => '040', 'name' => 'Storage', 'values' => json_encode(['128 gb', '256 gb'])],
        ];

        foreach ($productAttributes as $attribute) {
            ProductAttribute::create($attribute);
        }

       // Product Variations
        $productVariations = [
            [
                'product_id' => '001', 'stock' => 34, 'price' => 134.00, 'sale_price' => 122.60, 
                'image' => 'product/nike-shoes.png', 'attributes' => json_encode(['Color' => 'Green', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '001', 'stock' => 15, 'price' => 132.00, 'image' => 'product/NikeWildhorse.png', 
                'attributes' => json_encode(['Color' => 'Black', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '001', 'stock' => 0, 'price' => 234.00, 'image' => 'product/NikeWildhorse.png', 
                'attributes' => json_encode(['Color' => 'Black', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '001', 'stock' => 222, 'price' => 232.00, 'image' => 'product/nike-shoes.png', 
                'attributes' => json_encode(['Color' => 'Green', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '001', 'stock' => 0, 'price' => 334.00, 'image' => 'product/NikeAirMax.png', 
                'attributes' => json_encode(['Color' => 'Red', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '001', 'stock' => 11, 'price' => 332.00, 'image' => 'product/NikeAirMax.png', 
                'attributes' => json_encode(['Color' => 'Red', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '004', 'stock' => 34, 'price' => 134.00, 'sale_price' => 122.60, 
                'image' => 'product/tshirt_red_collar.png', 
                'attributes' => json_encode(['Color' => 'Red', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '004', 'stock' => 15, 'price' => 132.00, 'image' => 'product/tshirt_red_collar.png', 
                'attributes' => json_encode(['Color' => 'Red', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '004', 'stock' => 0, 'price' => 234.00, 'image' => 'product/tshirt_yellow_collar.png', 
                'attributes' => json_encode(['Color' => 'Yellow', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '004', 'stock' => 222, 'price' => 232.00, 'image' => 'product/tshirt_yellow_collar.png', 
                'attributes' => json_encode(['Color' => 'Yellow', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '004', 'stock' => 0, 'price' => 334.00, 'image' => 'product/tshirt_green_collar.png', 
                'attributes' => json_encode(['Color' => 'Green', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '004', 'stock' => 11, 'price' => 332.00, 'image' => 'product/tshirt_green_collar.png', 
                'attributes' => json_encode(['Color' => 'Green', 'Size' => 'EU 30'])
            ],
            [
                'product_id' => '004', 'stock' => 0, 'price' => 334.00, 'image' => 'product/tshirt_blue_collar.png', 
                'attributes' => json_encode(['Color' => 'Blue', 'Size' => 'EU 30'])
            ],
            [
                'product_id' => '004', 'stock' => 11, 'price' => 332.00, 'image' => 'product/tshirt_blue_collar.png', 
                'attributes' => json_encode(['Color' => 'Blue', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '005', 'stock' => 16, 'price' => 36.00, 'sale_price' => 12.60, 
                'image' => 'product/NikeAirJOrdonOrange.png', 
                'attributes' => json_encode(['Color' => 'Orange', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '005', 'stock' => 15, 'price' => 35.00, 'image' => 'product/NikeAirJOrdonBlackRed.png', 
                'attributes' => json_encode(['Color' => 'Black', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '005', 'stock' => 14, 'price' => 34.00, 'image' => 'product/NikeAirJordonwhiteMagenta.png', 
                'attributes' => json_encode(['Color' => 'Brown', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '005', 'stock' => 13, 'price' => 33.00, 'image' => 'product/NikeAirJOrdonBlackRed.png', 
                'attributes' => json_encode(['Color' => 'Black', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '005', 'stock' => 12, 'price' => 32.00, 'image' => 'product/NikeAirJordonwhiteMagenta.png', 
                'attributes' => json_encode(['Color' => 'Brown', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '005', 'stock' => 11, 'price' => 31.00, 'image' => 'product/NikeAirJOrdonOrange.png', 
                'attributes' => json_encode(['Color' => 'Orange', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '022', 'stock' => 16, 'price' => 36.00, 'sale_price' => 12.60, 
                'image' => 'product/bedroom_bed.png', 
                'attributes' => json_encode(['Color' => 'Brown', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '022', 'stock' => 15, 'price' => 35.00, 'image' => 'product/bedroom_bed_simple_brown.png', 
                'attributes' => json_encode(['Color' => 'Brown', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '022', 'stock' => 14, 'price' => 34.00, 'image' => 'product/bedroom_bed_with_comforter.png', 
                'attributes' => json_encode(['Color' => 'Brown', 'Size' => 'EU 30'])
            ],
            [
                'product_id' => '022', 'stock' => 13, 'price' => 33.00, 'image' => 'product/bedroom_bed_black.png', 
                'attributes' => json_encode(['Color' => 'Black', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '022', 'stock' => 12, 'price' => 32.00, 'image' => 'product/bedroom_bed_black.png', 
                'attributes' => json_encode(['Color' => 'Black', 'Size' => 'EU 34'])
            ],
            [
                'product_id' => '022', 'stock' => 11, 'price' => 31.00, 'image' => 'product/bedroom_bed_grey.png', 
                'attributes' => json_encode(['Color' => 'Grey', 'Size' => 'EU 32'])
            ],
            [
                'product_id' => '033', 'stock' => 16, 'price' => 400.00, 'sale_price' => 350.00, 
                'image' => 'product/acer_laptop_var_1.png', 
                'attributes' => json_encode(['Ram' => '6', 'hard' => '512'])
            ],
            [
                'product_id' => '033', 'stock' => 15, 'price' => 450.00, 'image' => 'product/acer_laptop_1.png', 
                'attributes' => json_encode(['Ram' => '8', 'hard' => '512'])
            ],
            [
                'product_id' => '033', 'stock' => 14, 'price' => 470.00, 'image' => 'product/acer_laptop_var_4.png', 
                'attributes' => json_encode(['Ram' => '8', 'hard' => '1 tb'])
            ],
            [
                'product_id' => '033', 'stock' => 13, 'price' => 500.00, 'image' => 'product/acer_laptop_var_3.png', 
                'attributes' => json_encode(['Ram' => '16', 'hard' => '512'])
            ],
            [
                'product_id' => '033', 'stock' => 12, 'price' => 650.00, 'image' => 'product/acer_laptop_var_2.png', 
                'attributes' => json_encode(['Ram' => '16', 'hard' => '1 tb'])
            ],
            [
                'product_id' => '033', 'stock' => 11, 'price' => 800.00, 'image' => 'product/acer_laptop_var_4.png', 
                'attributes' => json_encode(['Ram' => '16', 'hard' => '2 tb'])
            ],
            [
                'product_id' => '040', 'stock' => 16, 'price' => 400.00, 'sale_price' => 350.00, 
                'image' => 'product/iphone_12_red.png', 
                'attributes' => json_encode(['Color' => 'Red', 'Storage' => '128 gb'])
            ],
            [
                'product_id' => '040', 'stock' => 15, 'price' => 450.00, 'image' => 'product/iphone_12_red.png', 
                'attributes' => json_encode(['Color' => 'Red', 'Storage' => '256 gb'])
            ],
            [
                'product_id' => '040', 'stock' => 14, 'price' => 470.00, 'image' => 'product/iphone_12_blue.png', 
                'attributes' => json_encode(['Color' => 'Blue', 'Storage' => '128 gb'])
            ],
            [
                'product_id' => '040', 'stock' => 13, 'price' => 500.00, 'image' => 'product/iphone_12_blue.png', 
                'attributes' => json_encode(['Color' => 'Blue', 'Storage' => '256 gb'])
            ],
            [
                'product_id' => '040', 'stock' => 12, 'price' => 650.00, 'image' => 'product/iphone_12_green.png', 
                'attributes' => json_encode(['Color' => 'Green', 'Storage' => '128 gb'])
            ],
            [
                'product_id' => '040', 'stock' => 11, 'price' => 800.00, 'image' => 'product/iphone_12_black.png', 
                'attributes' => json_encode(['Color' => 'Black', 'Storage' => '128 gb'])
            ],
        ];

        foreach ($productVariations as $variation) {
            ProductVariation::create($variation);
        }

        // Product Images
        $productImages = [
            ['product_id' => '001', 'image_path' => 'product/nike-shoes.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '001', 'image_path' => 'product/NikeWildhorse.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '001', 'image_path' => 'product/NikeAirMax.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '001', 'image_path' => 'product/NikeAirJordonwhiteMagenta.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '002', 'image_path' => 'product/tshirt_blue_without_collar_back.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '002', 'image_path' => 'product/tshirt_blue_without_collar_front.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '002', 'image_path' => 'product/product-shirt.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '003', 'image_path' => 'product/leather_jacket_1.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '003', 'image_path' => 'product/leather_jacket_2.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '003', 'image_path' => 'product/leather_jacket_3.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '003', 'image_path' => 'product/leather_jacket_4.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '004', 'image_path' => 'product/tshirt_red_collar.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '004', 'image_path' => 'product/tshirt_yellow_collar.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '004', 'image_path' => 'product/tshirt_green_collar.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '004', 'image_path' => 'product/tshirt_blue_collar.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '005', 'image_path' => 'product/NikeAirJOrdonBlackRed.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '005', 'image_path' => 'product/NikeAirJOrdonOrange.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '005', 'image_path' => 'product/NikeAirJordonwhiteMagenta.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '005', 'image_path' => 'product/NikeAirJOrdonWhiteRed.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '006', 'image_path' => 'product/samsung_s9_mobile.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '006', 'image_path' => 'product/samsung_s9_mobile_withback.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '006', 'image_path' => 'product/samsung_s9_mobile_back.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '008', 'image_path' => 'product/iphone8_mobile.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '008', 'image_path' => 'product/iphone8_mobile_back.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '008', 'image_path' => 'product/iphone8_mobile_dual_side.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '008', 'image_path' => 'product/iphone8_mobile_front.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '009', 'image_path' => 'product/NikeAirJordonSingleBlue.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '009', 'image_path' => 'product/NikeAirJordonSingleOrange.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '009', 'image_path' => 'product/NikeAirMax.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '009', 'image_path' => 'product/NikeBasketballShoeGreenBlack.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '010', 'image_path' => 'product/NikeAirJordonSingleOrange.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '010', 'image_path' => 'product/NikeWildhorse.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '010', 'image_path' => 'product/NikeAirMax.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '010', 'image_path' => 'product/NikeBasketballShoeGreenBlack.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '011', 'image_path' => 'product/NikeAirMax.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '011', 'image_path' => 'product/NikeAirJordonSingleOrange.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '011', 'image_path' => 'product/NikeAirJordonSingleBlue.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '011', 'image_path' => 'product/NikeBasketballShoeGreenBlack.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '012', 'image_path' => 'product/NikeBasketballShoeGreenBlack.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '012', 'image_path' => 'product/NikeAirJordonSingleOrange.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '012', 'image_path' => 'product/NikeAirMax.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '012', 'image_path' => 'product/NikeWildhorse.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '013', 'image_path' => 'product/NikeWildhorse.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '013', 'image_path' => 'product/NikeAirJordonSingleOrange.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '013', 'image_path' => 'product/NikeAirMax.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '013', 'image_path' => 'product/NikeBasketballShoeGreenBlack.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '014', 'image_path' => 'product/tracksuit_red.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '014', 'image_path' => 'product/tracksuit_black.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '014', 'image_path' => 'product/tracksuit_blue.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '014', 'image_path' => 'product/trcksuit_parrotgreen.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '015', 'image_path' => 'product/tracksuit_black.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '015', 'image_path' => 'product/tracksuit_red.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '015', 'image_path' => 'product/tracksuit_blue.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '015', 'image_path' => 'product/trcksuit_parrotgreen.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '016', 'image_path' => 'product/tracksuit_blue.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '016', 'image_path' => 'product/tracksuit_black.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '016', 'image_path' => 'product/tracksuit_red.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '016', 'image_path' => 'product/trcksuit_parrotgreen.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '017', 'image_path' => 'product/trcksuit_parrotgreen.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '017', 'image_path' => 'product/tracksuit_black.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '017', 'image_path' => 'product/tracksuit_blue.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '017', 'image_path' => 'product/tracksuit_red.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '018', 'image_path' => 'product/Adidas_Football.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '018', 'image_path' => 'product/baseball_bat.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '018', 'image_path' => 'product/cricket_bat.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '018', 'image_path' => 'product/tennis_racket.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '019', 'image_path' => 'product/baseball_bat.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '019', 'image_path' => 'product/Adidas_Football.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '019', 'image_path' => 'product/cricket_bat.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '019', 'image_path' => 'product/tennis_racket.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '020', 'image_path' => 'product/cricket_bat.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '020', 'image_path' => 'product/baseball_bat.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '020', 'image_path' => 'product/Adidas_Football.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '020', 'image_path' => 'product/tennis_racket.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '021', 'image_path' => 'product/tennis_racket.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '021', 'image_path' => 'product/baseball_bat.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '021', 'image_path' => 'product/cricket_bat.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '021', 'image_path' => 'product/Adidas_Football.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '022', 'image_path' => 'product/bedroom_bed_black.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '022', 'image_path' => 'product/bedroom_bed_grey.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '022', 'image_path' => 'product/bedroom_bed_simple_brown.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '022', 'image_path' => 'product/bedroom_bed_with_comforter.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '033', 'image_path' => 'product/acer_laptop_var_1.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '033', 'image_path' => 'product/acer_laptop_1.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '033', 'image_path' => 'product/acer_laptop_var_2.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '033', 'image_path' => 'product/acer_laptop_var_3.png', 'is_primary' => false, 'sort_order' => 3],
            ['product_id' => '040', 'image_path' => 'product/iphone_12_red.png', 'is_primary' => true, 'sort_order' => 0],
            ['product_id' => '040', 'image_path' => 'product/iphone_12_blue.png', 'is_primary' => false, 'sort_order' => 1],
            ['product_id' => '040', 'image_path' => 'product/iphone_12_green.png', 'is_primary' => false, 'sort_order' => 2],
            ['product_id' => '040', 'image_path' => 'product/iphone_12_black.png', 'is_primary' => false, 'sort_order' => 3],
        ];

        foreach ($productImages as $image) {
            ProductImage::create($image);
        }

      
         // Product Categories (Fixed: Ensured no duplicates)
        $productCategories = [
            ['product_id' => '001', 'category_id' => 1],
            ['product_id' => '001', 'category_id' => 8],
            ['product_id' => '004', 'category_id' => 3],
            ['product_id' => '004', 'category_id' => 16],
            ['product_id' => '005', 'category_id' => 1],
            ['product_id' => '005', 'category_id' => 8],
            ['product_id' => '006', 'category_id' => 2],
            ['product_id' => '007', 'category_id' => 4],
            ['product_id' => '008', 'category_id' => 4],
            ['product_id' => '009', 'category_id' => 1],
            ['product_id' => '009', 'category_id' => 8],
            ['product_id' => '010', 'category_id' => 1],
            ['product_id' => '010', 'category_id' => 8],
            ['product_id' => '011', 'category_id' => 1],
            ['product_id' => '011', 'category_id' => 8],
            ['product_id' => '012', 'category_id' => 1],
            ['product_id' => '012', 'category_id' => 8],
            ['product_id' => '013', 'category_id' => 1],
            ['product_id' => '013', 'category_id' => 8],
            ['product_id' => '014', 'category_id' => 1],
            ['product_id' => '014', 'category_id' => 8],
            ['product_id' => '015', 'category_id' => 1],
            ['product_id' => '015', 'category_id' => 9],
            ['product_id' => '016', 'category_id' => 1],
            ['product_id' => '016', 'category_id' => 9],
            ['product_id' => '017', 'category_id' => 1],
            ['product_id' => '017', 'category_id' => 9],
            ['product_id' => '018', 'category_id' => 1],
            ['product_id' => '018', 'category_id' => 10],
            ['product_id' => '019', 'category_id' => 1],
            ['product_id' => '019', 'category_id' => 10],
            ['product_id' => '020', 'category_id' => 1],
            ['product_id' => '020', 'category_id' => 10],
            ['product_id' => '021', 'category_id' => 1],
            ['product_id' => '021', 'category_id' => 10],
            ['product_id' => '022', 'category_id' => 5],
            ['product_id' => '022', 'category_id' => 11],
            ['product_id' => '023', 'category_id' => 5],
            ['product_id' => '023', 'category_id' => 11],
            ['product_id' => '024', 'category_id' => 5],
            ['product_id' => '024', 'category_id' => 11],
            ['product_id' => '025', 'category_id' => 5],
            ['product_id' => '025', 'category_id' => 11],
            ['product_id' => '026', 'category_id' => 5],
            ['product_id' => '026', 'category_id' => 12],
            ['product_id' => '027', 'category_id' => 5],
            ['product_id' => '027', 'category_id' => 12],
            ['product_id' => '028', 'category_id' => 5],
            ['product_id' => '028', 'category_id' => 12],
            ['product_id' => '029', 'category_id' => 5],
            ['product_id' => '029', 'category_id' => 13],
            ['product_id' => '030', 'category_id' => 5],
            ['product_id' => '030', 'category_id' => 13],
            ['product_id' => '031', 'category_id' => 5],
            ['product_id' => '031', 'category_id' => 13],
            ['product_id' => '032', 'category_id' => 5],
            ['product_id' => '032', 'category_id' => 13],
            ['product_id' => '033', 'category_id' => 2],
            ['product_id' => '033', 'category_id' => 14],
            ['product_id' => '034', 'category_id' => 2],
            ['product_id' => '034', 'category_id' => 14],
            ['product_id' => '035', 'category_id' => 2],
            ['product_id' => '035', 'category_id' => 14],
            ['product_id' => '036', 'category_id' => 2],
            ['product_id' => '036', 'category_id' => 14],
            ['product_id' => '037', 'category_id' => 2],
            ['product_id' => '037', 'category_id' => 15],
            ['product_id' => '038', 'category_id' => 2],
            ['product_id' => '038', 'category_id' => 15],
            ['product_id' => '039', 'category_id' => 2],
            ['product_id' => '039', 'category_id' => 15],
            ['product_id' => '040', 'category_id' => 2],
            ['product_id' => '040', 'category_id' => 15],
        ];

        // Insert into product_category pivot table, skipping duplicates
        $uniqueCategories = collect($productCategories)->unique(function ($item) {
            return $item['product_id'] . '-' . $item['category_id'];
        })->values()->all();

        foreach ($uniqueCategories as $productCategory) {
            DB::table('product_category')->insertOrIgnore($productCategory);
        }

        // Product Brands
        $productBrands = [
            ['product_id' => '001', 'brand_id' => 1],
            ['product_id' => '002', 'brand_id' => 6],
            ['product_id' => '003', 'brand_id' => 6],
            ['product_id' => '004', 'brand_id' => 6],
            ['product_id' => '005', 'brand_id' => 1],
            ['product_id' => '006', 'brand_id' => 7],
            ['product_id' => '007', 'brand_id' => 7],
            ['product_id' => '008', 'brand_id' => 5],
            ['product_id' => '009', 'brand_id' => 1],
            ['product_id' => '010', 'brand_id' => 1],
            ['product_id' => '011', 'brand_id' => 1],
            ['product_id' => '012', 'brand_id' => 1],
            ['product_id' => '013', 'brand_id' => 1],
            ['product_id' => '014', 'brand_id' => 1],
            ['product_id' => '015', 'brand_id' => 1],
            ['product_id' => '016', 'brand_id' => 1],
            ['product_id' => '017', 'brand_id' => 1],
            ['product_id' => '018', 'brand_id' => 2],
            ['product_id' => '019', 'brand_id' => 2],
            ['product_id' => '020', 'brand_id' => 2],
            ['product_id' => '021', 'brand_id' => 2],
            ['product_id' => '022', 'brand_id' => 8],
            ['product_id' => '023', 'brand_id' => 8],
            ['product_id' => '024', 'brand_id' => 8],
            ['product_id' => '025', 'brand_id' => 8],
            ['product_id' => '026', 'brand_id' => 2],
            ['product_id' => '027', 'brand_id' => 2],
            ['product_id' => '028', 'brand_id' => 2],
            ['product_id' => '029', 'brand_id' => 9],
            ['product_id' => '030', 'brand_id' => 9],
            ['product_id' => '031', 'brand_id' => 9],
            ['product_id' => '032', 'brand_id' => 9],
            ['product_id' => '033', 'brand_id' => 10],
            ['product_id' => '034', 'brand_id' => 10],
            ['product_id' => '035', 'brand_id' => 10],
            ['product_id' => '036', 'brand_id' => 10],
            ['product_id' => '037', 'brand_id' => 5],
            ['product_id' => '038', 'brand_id' => 5],
            ['product_id' => '039', 'brand_id' => 5],
            ['product_id' => '040', 'brand_id' => 5],
        ];

        // Insert into product_brand pivot table
        foreach ($productBrands as $productBrand) {
            DB::table('product_brand')->insert($productBrand);
        }

       
       // Banners (Fixed: Aligned with Banner model fields)
        $banners = [
            ['image_url' => 'banner/banner_1.png', 'target_screen' => 'home', 'active' => true],
            ['image_url' => 'banner/banner_2.png', 'target_screen' => 'products', 'active' => true],
            ['image_url' => 'banner/banner_3.png', 'target_screen' => 'categories', 'active' => true],
            ['image_url' => 'banner/banner_4.png', 'target_screen' => 'home', 'active' => true],
            ['image_url' => 'banner/banner_5.png', 'target_screen' => 'products', 'active' => true],
            ['image_url' => 'banner/banner_6.png', 'target_screen' => 'categories', 'active' => true],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
  