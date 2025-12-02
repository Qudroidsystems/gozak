<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class CategoryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'View category',
            'Create category',
            'Update category',
            'Delete category',
        ];

        foreach ($permissions as $permission) {
            $str = $permission;
            $delimiter = ' ';
            $words = explode($delimiter, $str);

            foreach ($words as $word) {
                if ($word == "category") {
                    Permission::create([
                        'name'  => $permission,
                        'title' => "Category Management"
                    ]);
                }
            }
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}