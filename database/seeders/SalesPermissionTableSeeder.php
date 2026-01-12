<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SalesPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View sale',
            'Create sale',
            'Update sale',
            'Delete sale',
            'View sales report',
            'Export sales report',
            'View sales commission',
            'Manage sales commission',
            'View sales analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['title' => 'Sales Management']
            );
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
