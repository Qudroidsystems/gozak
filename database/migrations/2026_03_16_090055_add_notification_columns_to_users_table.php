<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // ── FCM / Push notification ───────────────────────────────────
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token', 500)->nullable()->after('remember_token');
            }

            // ── Notification preference toggles ───────────────────────────
            if (!Schema::hasColumn('users', 'push_notifications_enabled')) {
                $table->boolean('push_notifications_enabled')->default(true)->after('fcm_token');
            }

            if (!Schema::hasColumn('users', 'order_updates_enabled')) {
                $table->boolean('order_updates_enabled')->default(true)->after('push_notifications_enabled');
            }

            if (!Schema::hasColumn('users', 'promotional_notifications_enabled')) {
                $table->boolean('promotional_notifications_enabled')->default(true)->after('order_updates_enabled');
            }

            if (!Schema::hasColumn('users', 'security_alerts_enabled')) {
                $table->boolean('security_alerts_enabled')->default(true)->after('promotional_notifications_enabled');
            }

            if (!Schema::hasColumn('users', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')->default(true)->after('security_alerts_enabled');
            }

            // ── Notification analytics ────────────────────────────────────
            if (!Schema::hasColumn('users', 'last_notification_at')) {
                $table->timestamp('last_notification_at')->nullable()->after('email_notifications_enabled');
            }

            if (!Schema::hasColumn('users', 'notification_count')) {
                $table->unsignedInteger('notification_count')->default(0)->after('last_notification_at');
            }

            // ── Device / app info ─────────────────────────────────────────
            if (!Schema::hasColumn('users', 'last_device_platform')) {
                $table->string('last_device_platform', 20)->nullable()->after('notification_count');
            }

            if (!Schema::hasColumn('users', 'last_app_version')) {
                $table->string('last_app_version', 20)->nullable()->after('last_device_platform');
            }

            // ── Quiet hours (optional — for future "do not disturb" feature)
            if (!Schema::hasColumn('users', 'quiet_hours_start')) {
                $table->time('quiet_hours_start')->nullable()->after('last_app_version');
            }

            if (!Schema::hasColumn('users', 'quiet_hours_end')) {
                $table->time('quiet_hours_end')->nullable()->after('quiet_hours_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'fcm_token',
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
            ];

            // Only drop columns that actually exist
            $existing = array_filter(
                $columns,
                fn($col) => Schema::hasColumn('users', $col)
            );

            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
