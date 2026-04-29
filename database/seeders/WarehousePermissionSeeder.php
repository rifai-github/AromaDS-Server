<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\User;

class WarehousePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create warehouse permissions
        $warehousePermissions = [
            // Master Products permissions
            'master-products.view' => 'View master products',
            'master-products.create' => 'Create master products',
            'master-products.edit' => 'Edit master products',
            'master-products.delete' => 'Delete master products',
            
            // Master Rentals permissions
            'master-rentals.view' => 'View master rentals',
            'master-rentals.create' => 'Create master rentals',
            'master-rentals.edit' => 'Edit master rentals',
            'master-rentals.delete' => 'Delete master rentals',
            
            // Product Types permissions
            'product-types.view' => 'View product types',
            'product-types.create' => 'Create product types',
            'product-types.edit' => 'Edit product types',
            'product-types.delete' => 'Delete product types',
            
            // Products permissions
            'products.view' => 'View products',
            'products.create' => 'Create products',
            'products.edit' => 'Edit products',
            'products.delete' => 'Delete products',
            
            // Serial Numbers permissions
            'serial-numbers.view' => 'View serial numbers',
            'serial-numbers.create' => 'Create serial numbers',
            'serial-numbers.edit' => 'Edit serial numbers',
            'serial-numbers.delete' => 'Delete serial numbers',
            
            // Stock Adjustments permissions
            'stock-adjustments.view' => 'View stock adjustments',
            'stock-adjustments.create' => 'Create stock adjustments',
            'stock-adjustments.edit' => 'Edit stock adjustments',
            'stock-adjustments.delete' => 'Delete stock adjustments',
            
            // Stock Opnames permissions
            'stock-opnames.view' => 'View stock opnames',
            'stock-opnames.create' => 'Create stock opnames',
            'stock-opnames.edit' => 'Edit stock opnames',
            'stock-opnames.delete' => 'Delete stock opnames',
            
            // Unit on Walls permissions
            'unit-on-walls.view' => 'View unit on walls',
            'unit-on-walls.create' => 'Create unit on walls',
            'unit-on-walls.edit' => 'Edit unit on walls',
            'unit-on-walls.delete' => 'Delete unit on walls',
            
            // Company permissions
            'companies.view' => 'View companies',
            'companies.create' => 'Create companies',
            'companies.edit' => 'Edit companies',
            'companies.delete' => 'Delete companies',
            
            'branches.view' => 'View branches',
            'branches.create' => 'Create branches',
            'branches.edit' => 'Edit branches',
            'branches.delete' => 'Delete branches',
            
            'customers.view' => 'View customers',
            'customers.create' => 'Create customers',
            'customers.edit' => 'Edit customers',
            'customers.delete' => 'Delete customers',
            
            'company-virtual-accounts.view' => 'View company virtual accounts',
            'company-virtual-accounts.create' => 'Create company virtual accounts',
            'company-virtual-accounts.edit' => 'Edit company virtual accounts',
            'company-virtual-accounts.delete' => 'Delete company virtual accounts',
            
            'customer-taxes.view' => 'View customer taxes',
            'customer-taxes.create' => 'Create customer taxes',
            'customer-taxes.edit' => 'Edit customer taxes',
            'customer-taxes.delete' => 'Delete customer taxes',
            
            'bank-payments.view' => 'View bank payments',
            'bank-payments.create' => 'Create bank payments',
            'bank-payments.edit' => 'Edit bank payments',
            'bank-payments.delete' => 'Delete bank payments',
            
            'master-price-slabs.view' => 'View master price slabs',
            'master-price-slabs.create' => 'Create master price slabs',
            'master-price-slabs.edit' => 'Edit master price slabs',
            'master-price-slabs.delete' => 'Delete master price slabs',
            
            'access-management.view' => 'View access management',
            'access-management.create' => 'Create access management',
            'access-management.edit' => 'Edit access management',
            'access-management.delete' => 'Delete access management',
            
            // Suppliers permissions
            'suppliers.view' => 'View suppliers',
            'suppliers.create' => 'Create suppliers',
            'suppliers.edit' => 'Edit suppliers',
            'suppliers.delete' => 'Delete suppliers',
            
            // Warehouses permissions
            'warehouses.view' => 'View warehouses',
            'warehouses.create' => 'Create warehouses',
            'warehouses.edit' => 'Edit warehouses',
            'warehouses.delete' => 'Delete warehouses',
            
            // Bank Receipts permissions
            'bank-receipts.view' => 'View bank receipts',
            'bank-receipts.create' => 'Create bank receipts',
            'bank-receipts.edit' => 'Edit bank receipts',
            'bank-receipts.delete' => 'Delete bank receipts',
            
            // Billing Groups permissions
            'billing-groups.view' => 'View billing groups',
            'billing-groups.create' => 'Create billing groups',
            'billing-groups.edit' => 'Edit billing groups',
            'billing-groups.delete' => 'Delete billing groups',
            
            // Faktur Pajak permissions
            'faktur-pajak.view' => 'View faktur pajak',
            'faktur-pajak.create' => 'Create faktur pajak',
            'faktur-pajak.edit' => 'Edit faktur pajak',
            'faktur-pajak.delete' => 'Delete faktur pajak',
            
            // Invoice Follow Ups permissions
            'invoice-follow-ups.view' => 'View invoice follow ups',
            'invoice-follow-ups.create' => 'Create invoice follow ups',
            'invoice-follow-ups.edit' => 'Edit invoice follow ups',
            'invoice-follow-ups.delete' => 'Delete invoice follow ups',
            
            // Invoice Forms permissions
            'invoice-forms.view' => 'View invoice forms',
            'invoice-forms.create' => 'Create invoice forms',
            'invoice-forms.edit' => 'Edit invoice forms',
            'invoice-forms.delete' => 'Delete invoice forms',
            'invoices.view' => 'View invoices',
            'invoices.create' => 'Create invoices',
            'invoices.edit' => 'Edit invoices',
            'invoices.delete' => 'Delete invoices',
        ];

        foreach ($warehousePermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'system_reserved' => true,
                    'is_active' => true
                ]
            );
        }

        // Assign warehouse permissions to the first user (assuming it's an admin)
        $adminUser = User::first();
        if ($adminUser) {
            $warehousePermissionIds = Permission::whereIn('name', array_keys($warehousePermissions))->pluck('id');
            $adminUser->permissions()->syncWithoutDetaching($warehousePermissionIds);
        }
    }
}
