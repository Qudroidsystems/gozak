<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // FCM Tokens (multiple devices support)
            $table->json('fcm_tokens')->nullable()->after('remember_token');
            
            // Notification preferences
            $table->boolean('push_notifications_enabled')->default(true)->after('fcm_tokens');
            $table->boolean('order_updates_enabled')->default(true)->after('push_notifications_enabled');
            $table->boolean('promotional_notifications_enabled')->default(false)->after('order_updates_enabled');
            $table->boolean('security_alerts_enabled')->default(true)->after('promotional_notifications_enabled');
            $table->boolean('email_notifications_enabled')->default(true)->after('security_alerts_enabled');
            
            // Notification tracking
            $table->timestamp('last_notification_at')->nullable()->after('email_notifications_enabled');
            $table->unsignedInteger('notification_count')->default(0)->after('last_notification_at');
            
            // Device information
            $table->string('last_device_platform')->nullable()->after('notification_count');
            $table->string('last_app_version')->nullable()->after('last_device_platform');
            
            // Quiet hours
            $table->time('quiet_hours_start')->nullable()->after('last_app_version');
            $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');
        });

        // Add barcode column to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('barcode_path')->nullable()->after('status');
            $table->string('barcode_data')->nullable()->after('barcode_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'fcm_tokens',
                'push_notifications_enabled',
                'order_updates_enabled', 
                'promotional_notifications_enabled',
                'security_alerts_enabled',
                'email_notifications_enabled',
                'last_notification_at',
                'notification_count',
                'last_device_platform',
                'last_app_version',
                'quiet_hours_start',
                'quiet_hours_end',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['barcode_path', 'barcode_data']);
        });
    }
};