<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * "App Users" — the default role for every customer of the mobile app.
 *
 * It only carries self-service permissions (my profile, my addresses, my
 * orders, my reviews, my account). It deliberately does NOT get any admin
 * permission such as "user-edit", which would let a customer manage other
 * users in the admin panel.
 *
 * Existing users that have no role at all are given "App Users" here;
 * staff (anyone who already has a role) are left alone.
 */
return new class extends Migration
{
    public const ROLE = 'App Users';

    public const PERMISSIONS = [
        'Update own profile'   => 'Edit their own name, phone, gender, date of birth and photo in the app.',
        'Delete own account'   => 'Close their own account from the app.',
        'Manage own addresses' => 'Add, edit and delete their own delivery addresses.',
        'Place orders'         => 'Check out and pay for orders in the app.',
        'Cancel own orders'    => 'Cancel their own orders before they ship.',
        'Write reviews'        => 'Review products they bought.',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions')) {
            return;
        }

        $perms = [];
        foreach (self::PERMISSIONS as $name => $description) {
            $perms[] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['title' => $name, 'description' => $description]
            );
        }

        $attrs = Schema::hasColumn('roles', 'badge') ? ['badge' => 'badge bg-info'] : [];
        $role  = Role::firstOrCreate(['name' => self::ROLE, 'guard_name' => 'web'], $attrs);
        $role->givePermissionTo($perms);

        // Backfill: every user without any role becomes an App User.
        $morph = (new User)->getMorphClass();
        $withRole = DB::table('model_has_roles')->where('model_type', $morph)->pluck('model_id');

        User::whereNotIn('id', $withRole)->select('id')->chunkById(500, function ($users) use ($role, $morph) {
            $rows = $users->map(fn ($u) => [
                'role_id'    => $role->id,
                'model_type' => $morph,
                'model_id'   => $u->id,
            ])->all();
            DB::table('model_has_roles')->insertOrIgnore($rows);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $role = Role::where('name', self::ROLE)->first();
        if ($role) {
            DB::table('model_has_roles')->where('role_id', $role->id)->delete();
            $role->delete();
        }
        Permission::whereIn('name', array_keys(self::PERMISSIONS))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
