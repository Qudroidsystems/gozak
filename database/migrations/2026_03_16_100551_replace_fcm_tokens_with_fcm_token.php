<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // ── 1. Add fcm_token (single string) if it doesn't exist ─────
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token', 500)->nullable()->after('remember_token');
            }

            // ── 2. Copy the first token out of the fcm_tokens JSON array
            //       into fcm_token so no data is lost ──────────────────────
            if (Schema::hasColumn('users', 'fcm_tokens')) {
                // MySQL / MariaDB — extract first token from JSON array
                DB::statement("
                    UPDATE users
                    SET fcm_token = JSON_UNQUOTE(JSON_EXTRACT(fcm_tokens, '$[0].token'))
                    WHERE fcm_tokens IS NOT NULL
                      AND fcm_tokens != 'null'
                      AND fcm_tokens != '[]'
                      AND (fcm_token IS NULL OR fcm_token = '')
                ");

                // ── 3. Drop the old JSON column ───────────────────────────
                $table->dropColumn('fcm_tokens');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Re-add fcm_tokens if you ever roll back
            if (!Schema::hasColumn('users', 'fcm_tokens')) {
                $table->json('fcm_tokens')->nullable()->after('remember_token');
            }

            // Drop the single-token column
            if (Schema::hasColumn('users', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }
};
