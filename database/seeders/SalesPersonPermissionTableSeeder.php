<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SalesPersonPermissionTableSeeder extends Seeder
{
    public function run(): void
    {
        // Sales Person specific permissions
        $permissions = [
            // Sales Dashboard & Analytics
            'View personal sales dashboard',
            'View personal sales analytics',

            // Sales Management (Personal)
            'View personal sales',
            'Create sale',
            'Update own sale',
            'View sale details',

            // Commission Management
            'View personal commission',
            'Export personal commission report',

            // Reports & Export
            'View personal sales report',
            'Export personal sales report',

            // Performance Tracking
            'View personal performance metrics',
            'View personal customer statistics',

            // Product & Inventory
            'View products',
            'View product prices',
            'Check product availability',

            // Customer Management
            'View customers',
            'Create customer',
            'Update customer',

            // Payment Management
            'Process payment',
            'View payment history',

            // Order Management
            'Create order',
            'View own orders',
            'Update own order',
            'Cancel own order',

            // Invoice & Receipt
            'Generate invoice',
            'Print receipt',
            'View invoice history',

            // Settings (Personal)
            'Update personal profile',
            'Change personal password',

            // Notifications
            'View personal notifications',

            // Dashboard Widgets
            'View sales targets',
            'View achievement metrics',

            // Export & Print
            'Print sales slip',
            'Export sales data',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Create or update Sales Person role
        $salesPersonRole = Role::updateOrCreate(
            ['name' => 'salesperson'],
            ['guard_name' => 'web']
        );

        // Assign permissions to salesperson role
        $salesPersonPermissions = [
            'View personal sales dashboard',
            'View personal sales analytics',
            'View personal sales',
            'Create sale',
            'Update own sale',
            'View sale details',
            'View personal commission',
            'Export personal commission report',
            'View personal sales report',
            'Export personal sales report',
            'View personal performance metrics',
            'View personal customer statistics',
            'View products',
            'View product prices',
            'Check product availability',
            'View customers',
            'Create customer',
            'Update customer',
            'Process payment',
            'View payment history',
            'Create order',
            'View own orders',
            'Update own order',
            'Cancel own order',
            'Generate invoice',
            'Print receipt',
            'View invoice history',
            'Update personal profile',
            'Change personal password',
            'View personal notifications',
            'View sales targets',
            'View achievement metrics',
            'Print sales slip',
            'Export sales data',
        ];

        foreach ($salesPersonPermissions as $permission) {
            $salesPersonRole->givePermissionTo($permission);
        }

        // Create or update Sales Agent role
        $salesAgentRole = Role::updateOrCreate(
            ['name' => 'sales-agent'],
            ['guard_name' => 'web']
        );

        // Assign permissions to sales-agent role
        $salesAgentPermissions = [
            'View personal sales dashboard',
            'View personal sales',
            'Create sale',
            'View sale details',
            'View personal commission',
            'View products',
            'View product prices',
            'Check product availability',
            'View customers',
            'Create customer',
            'Process payment',
            'Create order',
            'View own orders',
            'Generate invoice',
            'Print receipt',
            'Update personal profile',
            'Change personal password',
            'View personal notifications',
        ];

        foreach ($salesAgentPermissions as $permission) {
            $salesAgentRole->givePermissionTo($permission);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
