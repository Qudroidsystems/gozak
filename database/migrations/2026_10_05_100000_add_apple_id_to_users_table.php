<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Sign in with Apple: Apple's stable user id ("sub"). */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'apple_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('apple_id', 191)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'apple_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['apple_id']);
                $table->dropColumn('apple_id');
            });
        }
    }
};
