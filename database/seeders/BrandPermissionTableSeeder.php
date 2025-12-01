<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BrandPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'View brand',
            'Create brand',
            'Update brand',
            'Delete brand',
        ];

        foreach ($permissions as $permission) {
            $str = $permission;
            $delimiter = ' ';
            $words = explode($delimiter, $str);

            foreach ($words as $word) {
                if ($word == "brand") {
                    Permission::create([
                        'name'  => $permission,
                        'title' => "Brand Management"
                    ]);
                }
            }
        }

        // Optional: Clear cache so new permissions are available immediately
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}