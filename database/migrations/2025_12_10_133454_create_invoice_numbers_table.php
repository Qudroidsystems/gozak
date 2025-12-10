<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('prefix')->default('INV');
            $table->integer('year')->default(2025);
            $table->integer('next_number')->default(1);
            $table->integer('padding')->default(4);
            $table->timestamps();
            $table->unique(['prefix', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_numbers');
    }
};