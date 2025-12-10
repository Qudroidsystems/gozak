<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OrderPermissionTableSeeder extends Seeder
{
    // database/seeders/OrderPermissionSeeder.php
    public function run(): void
    {
        $permissions = [
            'View order',
            'Manage order',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'title' => 'Order Management']);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}