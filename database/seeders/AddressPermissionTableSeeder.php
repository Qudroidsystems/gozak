<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AddressPermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'View addresses',    // Can view the addresses list and details
            'Manage addresses',  // Can create, edit, delete, and set default addresses
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['title' => 'Address Management']  // Optional: you can change the title if you want
            );
        }

        // Clear cached permissions to make sure new ones are available immediately
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
