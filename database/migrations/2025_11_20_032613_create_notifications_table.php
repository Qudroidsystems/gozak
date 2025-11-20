<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // order_created, order_updated, promotional, security, system
            $table->string('title');
            $table->text('body');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('sent_via')->default('fcm'); // fcm, email, fcm_email, sms
            $table->string('delivery_status')->default('pending'); // pending, sent, failed, delivered
            $table->json('fcm_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['type', 'created_at']);
            $table->index('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};