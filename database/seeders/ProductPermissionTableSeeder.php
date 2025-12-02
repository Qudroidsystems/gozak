<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ProductPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View product',
            'Create product',
            'Update product',
            'Delete product',
        ];

        foreach ($permissions as $permission) {
            $str = $permission;
            $delimiter = ' ';
            $words = explode($delimiter, $str);

            foreach ($words as $word) {
                if ($word == "product") {
                    Permission::create([
                        'name'  => $permission,
                        'title' => "Product Management"
                    ]);
                }
            }
        }

        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}