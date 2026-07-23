<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_banners', function (Blueprint $table) {
            $table->string('display_style')->nullable()->after('show_once_daily');
            // 'coupon' | 'voucher' | 'gradient' | null (null = auto-cycle for variety)

            $table->string('amount_text')->nullable()->after('display_style');
            $table->string('masked_user')->nullable()->after('amount_text');
            $table->string('from_label')->nullable()->after('masked_user');
            $table->string('type_label')->nullable()->after('from_label');
            $table->string('date_label')->nullable()->after('type_label');
            $table->string('conditions_text')->nullable()->after('date_label');
            $table->string('announcement_text')->nullable()->after('conditions_text');
        });
    }

    public function down(): void
    {
        Schema::table('promo_banners', function (Blueprint $table) {
            $table->dropColumn([
                'display_style',
                'amount_text',
                'masked_user',
                'from_label',
                'type_label',
                'date_label',
                'conditions_text',
                'announcement_text',
            ]);
        });
    }
};
