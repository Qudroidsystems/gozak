<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class InventoryPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View inventory' => 'Inventory Management',
            'Manage inventory' => 'Inventory Management',
            'Adjust stock' => 'Inventory Management',
            'Transfer stock' => 'Inventory Management',
            'Export inventory' => 'Inventory Management',
            'Import inventory' => 'Inventory Management',
            'View inventory reports' => 'Inventory Management',
            'Manage stock locations' => 'Inventory Management',
            'Bulk stock operations' => 'Inventory Management',
            'View stock levels' => 'Inventory Management',
            'Manage stock movements' => 'Inventory Management',
            'View stock history' => 'Inventory Management',
            'Manage stock adjustments' => 'Inventory Management',
            'View inventory dashboard' => 'Inventory Management',
            'Delete inventory transactions' => 'Inventory Management',
            'View low stock alerts' => 'Inventory Management',
            'Manage stock categories' => 'Inventory Management',
            'View stock valuation' => 'Inventory Management',
        ];

        foreach ($permissions as $permission => $module) {
            Permission::create([
                'name'  => $permission,
                'title' => $module
            ]);
        }

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}