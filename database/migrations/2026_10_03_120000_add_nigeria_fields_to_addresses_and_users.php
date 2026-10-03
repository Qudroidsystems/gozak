<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Richer Nigerian addresses (LGA, landmark, address type, alternate phone,
 * optional postal code) and an alternate phone number on the user profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('addresses', 'lga')) {
                $table->string('lga')->nullable()->after('state');
            }
            if (!Schema::hasColumn('addresses', 'landmark')) {
                $table->string('landmark')->nullable()->after('street');
            }
            if (!Schema::hasColumn('addresses', 'address_type')) {
                $table->string('address_type', 20)->default('home')->after('name');
            }
            if (!Schema::hasColumn('addresses', 'alternate_phone')) {
                $table->string('alternate_phone', 30)->nullable()->after('phone_number');
            }
        });

        // Postal codes are rarely used in Nigeria — make it optional.
        if (Schema::hasColumn('addresses', 'postal_code')) {
            Schema::table('addresses', function (Blueprint $table) {
                $table->string('postal_code')->nullable()->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'alternate_phone')) {
                $table->string('alternate_phone', 30)->nullable()->after('phone_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            foreach (['lga', 'landmark', 'address_type', 'alternate_phone'] as $c) {
                if (Schema::hasColumn('addresses', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'alternate_phone')) {
                $table->dropColumn('alternate_phone');
            }
        });
    }
};
