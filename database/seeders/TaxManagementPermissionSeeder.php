<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class TaxManagementPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Tax Invoices Permissions
            [
                'name' => 'tax-invoices.view',
                'description' => 'View tax invoices',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'tax-invoices.create',
                'description' => 'Create tax invoices',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'tax-invoices.edit',
                'description' => 'Edit tax invoices',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'tax-invoices.delete',
                'description' => 'Delete tax invoices',
                'system_reserved' => false,
                'is_active' => true,
            ],
            
            // Tax Reports Permissions
            [
                'name' => 'tax-reports.view',
                'description' => 'View tax reports',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'tax-reports.create',
                'description' => 'Create tax reports',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'tax-reports.edit',
                'description' => 'Edit tax reports',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'tax-reports.delete',
                'description' => 'Delete tax reports',
                'system_reserved' => false,
                'is_active' => true,
            ],
            
            // e-Materai Transactions Permissions
            [
                'name' => 'e-materai-transactions.view',
                'description' => 'View e-Materai transactions',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'e-materai-transactions.create',
                'description' => 'Create e-Materai transactions',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'e-materai-transactions.edit',
                'description' => 'Edit e-Materai transactions',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'e-materai-transactions.delete',
                'description' => 'Delete e-Materai transactions',
                'system_reserved' => false,
                'is_active' => true,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
