<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
        //    'Super role',
           'View user',
           'Create user',
           'Update user',
           'Delete user',
           
           'View role',
           'Create role',
           'Update role',
           'Delete role',
           'Add user-role',
           'Update user-role',
           'Remove user-role',

           'View permission',
           'Create permission',
           'Update permission',
           'Delete permission',

           'dashboard',
        ];

        foreach ($permissions as $permission) {
            $str = $permission;
            $delimiter = ' ';
            $words = explode($delimiter, $str);

            foreach ($words as $word) {
                if($word == "user")
                Permission::Create(['name' => $permission,'title'=>"User Management"]);

                if($word == "role" || $word == "user-role")
                Permission::Create(['name' => $permission,'title'=>"Role Management"]);

                if($word == "permission")
                Permission::Create(['name' => $permission,'title'=>"Permission Management"]);

                if($word == "dashboard")
                Permission::Create(['name' => $permission,'title'=>"Dashboard Management"]);
    
            }
            //  Permission::Create(['name' => $permission]);
        }
    }
}
