<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Settings › Store details
 * - Adds the "View store setting" / "Update store setting" permissions and gives them to Super Admin.
 * - Clears the template placeholders the table was created with ("My Supermarket",
 *   "+1 234 567 890", "123 Main Street…", "info@mysupermarket.com") so the website
 *   never shows fake details. Real values an admin has already entered are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_settings')) {
            $s = DB::table('store_settings')->orderBy('id')->first();
            if ($s) {
                $updates = [];
                if (trim((string) $s->store_name) === 'My Supermarket') $updates['store_name'] = 'Gozak Mart';
                if (trim((string) $s->phone) === '+1 234 567 890') $updates['phone'] = null;
                if (trim((string) $s->address) === '123 Main Street, City, Country') $updates['address'] = null;
                if (trim((string) $s->email) === 'info@mysupermarket.com') $updates['email'] = null;
                if (trim((string) $s->motto) === 'Quality Products, Affordable Prices') $updates['motto'] = null;
                if ($updates) {
                    DB::table('store_settings')->where('id', $s->id)->update($updates + ['updated_at' => now()]);
                }
            }
            cache()->forget('store_settings');
        }

        if (Schema::hasTable('invoice_settings')) {
            DB::table('invoice_settings')->where('company_phone', '+1 234 567 890')->update(['company_phone' => null]);
            DB::table('invoice_settings')->where('company_address', '123 Business Street, City, Country')->update(['company_address' => null]);
        }

        if (Schema::hasTable('permissions') && class_exists(\Spatie\Permission\Models\Permission::class)) {
            $role = \Spatie\Permission\Models\Role::where('name', 'Super Admin')->first();
            foreach ([
                'View store setting'   => 'See the store name and contact details.',
                'Update store setting' => 'Change the store name, logo and contact details shown to customers.',
            ] as $name => $description) {
                $perm = \Spatie\Permission\Models\Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    Schema::hasColumn('permissions', 'title') ? ['title' => 'Store details', 'description' => $description] : []
                );
                if ($role && !$role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            \Spatie\Permission\Models\Permission::whereIn('name', ['View store setting', 'Update store setting'])->delete();
        }
    }
};
