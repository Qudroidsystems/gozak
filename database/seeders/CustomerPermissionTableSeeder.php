<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CustomerPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // Create Customer Permissions
        $permissions = [
            'View customer',
            'Manage customer',     // edit, block, view orders, etc.
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ], [
                'title' => 'Customer Management',
            ]);
        }

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}