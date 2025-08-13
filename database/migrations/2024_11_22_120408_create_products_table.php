<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categories table (from previous migration, unchanged)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        // Brands table (from previous migration, unchanged)
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        // Products table (aligned with ProductModel)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('sku')->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('price', 8, 2);
            $table->decimal('sale_price', 8, 2)->nullable();
            $table->string('thumbnail')->nullable();
            $table->text('description')->nullable();
            $table->string('product_type');
            $table->integer('sold_quantity')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        // Product attributes table (aligned with ProductAttributeModel)
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., 'Color', 'Size'
            $table->json('values')->nullable(); // e.g., ['Red', 'Blue']
            $table->timestamps();
        });

        // Product variations table (aligned with ProductVariationModel)
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('sku')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->decimal('sale_price', 8, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->json('attributes')->nullable(); // e.g., {'color': 'Red', 'size': 'M'}
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // Product images table (aligned with ProductModel.images)
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Product category pivot table (optional, for multiple categories)
        Schema::create('product_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['product_id', 'category_id']);
        });

        // Product brand pivot table (optional, for multiple brands)
        Schema::create('product_brand', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['product_id', 'brand_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_brand');
        Schema::dropIfExists('product_category');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};