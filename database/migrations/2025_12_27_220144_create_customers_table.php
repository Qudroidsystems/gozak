<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->unique();
            $table->string('phone_number')->unique();
            $table->string('phone_number_2')->nullable();
            $table->text('home_address')->nullable();
            $table->text('office_address')->nullable();
            $table->enum('gender', ['male', 'female'])->default('male');
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('credit_balance', 12, 2)->default(0);
            $table->decimal('outstanding_balance', 12, 2)->default(0);
            $table->enum('customer_type', ['regular', 'wholesale', 'corporate'])->default('regular');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('loyalty_card_number')->nullable()->unique();
            $table->integer('loyalty_points')->default(0);
            $table->date('date_of_birth')->nullable();
            $table->string('company_name')->nullable();
            $table->string('tax_id_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('notes')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('identification_type')->nullable();
            $table->string('identification_number')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['first_name', 'last_name']);
            $table->index('phone_number');
            $table->index('email');
            $table->index('status');
            $table->index('customer_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
