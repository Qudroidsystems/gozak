<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->text('company_address');
            $table->string('company_phone');
            $table->string('company_email');
            $table->string('tax_name')->default('VAT');
            $table->decimal('tax_rate', 5, 2)->default(15.00);
            $table->string('logo')->nullable();
            $table->string('primary_color')->default('#0d6efd');
            $table->string('currency')->default('USD');
            $table->string('currency_symbol')->default('$');
            $table->string('language')->default('en');
            $table->boolean('show_qr_code')->default(true);
            $table->boolean('show_barcode')->default(true);
            $table->text('invoice_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};