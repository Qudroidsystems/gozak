<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name');
            $table->string('logo')->nullable(); // path to logo image
            $table->string('motto')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('tax_id')->nullable(); // e.g., VAT/TIN
            $table->text('footer_note')->nullable(); // e.g., "Thank you for shopping!"
            $table->timestamps();
        });

        // Insert default data
        \DB::table('store_settings')->insert([
            'store_name' => 'My Supermarket',
            'motto'      => 'Quality Products, Affordable Prices',
            'address'    => '123 Main Street, City, Country',
            'phone'      => '+1 234 567 890',
            'email'      => 'info@mysupermarket.com',
            'footer_note'=> 'Thank you for shopping with us!',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
