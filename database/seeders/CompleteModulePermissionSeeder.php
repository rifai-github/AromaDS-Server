<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompleteModulePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all module permissions
        $allPermissions = [
            // Marketing Module Permissions
            'marketing.dashboard' => 'Access Marketing Dashboard',
            'marketing.commissions.dashboard' => 'Access Commission Dashboard',

            // Pipeline CRUD Permissions
            'marketing.pipeline' => 'Access Pipeline',
            'marketing.pipeline.create' => 'Create Pipeline',
            'marketing.pipeline.update' => 'Update Pipeline',
            'marketing.pipeline.delete' => 'Delete Pipeline',

            // Surveys CRUD Permissions
            'marketing.surveys' => 'Access Surveys',
            'marketing.surveys.create' => 'Create Surveys',
            'marketing.surveys.update' => 'Update Surveys',
            'marketing.surveys.delete' => 'Delete Surveys',

            // Quotations CRUD Permissions
            'marketing.quotations' => 'Access Quotations',
            'marketing.quotations.create' => 'Create Quotations',
            'marketing.quotations.update' => 'Update Quotations',
            'marketing.quotations.delete' => 'Delete Quotations',

            // Contracts CRUD Permissions
            'marketing.contracts' => 'Access Contracts',
            'marketing.contracts.create' => 'Create Contracts',
            'marketing.contracts.update' => 'Update Contracts',
            'marketing.contracts.delete' => 'Delete Contracts',
            'marketing.contracts.download' => 'Download Contracts',
            'marketing.contracts.print' => 'Print Contracts',
            'marketing.contracts.approve' => 'Approve Contracts',
            'marketing.contracts.target.create' => 'Create Contract Target',
            'marketing.contracts.target.update' => 'Update Contract Target',
            'marketing.contract_files.create' => 'Create Contract Files',
            'marketing.contract_files.approve' => 'Approve Contract Files',
            'marketing.contract_files.delete' => 'Delete Contract Files',
            'marketing.contract-net.view' => 'View Contract Net',
            'marketing.contract-net.edit' => 'Edit Contract Net',
            'marketing.contract-net.approve' => 'Approve Contract Net',

            'marketing.contract-assigned' => 'Access Contract Assigned',
            'marketing.contract-switchings' => 'Access Contract Switchings',
            'marketing.job-advices' => 'Access Job Advices',
            'marketing.lost-unit-reports' => 'Access Lost Unit Reports',
            'marketing.customers' => 'Access Master Customer',
            'marketing.customer-contacts' => 'Access Customer Contacts',
            'marketing.customer-taxes' => 'Access Master Customer Tax',
            'marketing.customer-types' => 'Access Master Customer Type',
            'marketing.stock-view' => 'Access Stock View (Read-only)',
            'marketing.stock-view.view' => 'View Stock View (Read-only)',

            // Operational Module Permissions
            'operational.job-schedules' => 'Access Job Schedules',
            'operational.job-schedules.create' => 'Create Job Schedules',
            'operational.job-schedules.update' => 'Update Job Schedules',
            'operational.job-schedules.delete' => 'Delete Job Schedules',
            'operational.job-schedules.ba-date' => 'Edit Job Schedule BA Date',
            'operational.job-schedules.ba-date.update' => 'Update Job Schedule BA Date',
            'operational.job-schedules.approve-ba' => 'Approve Job Schedule BA Files',
            'operational.job-schedules.approve-material-return' => 'Approve Job Schedule Material Return',

            'operational.job-assign' => 'Access Job Assign',
            'operational.job-assign.create' => 'Create Job Assign',
            'operational.job-assign.update' => 'Update Job Assign',
            'operational.job-assign.delete' => 'Delete Job Assign',

            'operational.job-assign-team' => 'Access Job Assign Team',
            'operational.job-assign-team.create' => 'Create Job Assign Team',
            'operational.job-assign-team.update' => 'Update Job Assign Team',
            'operational.job-assign-team.delete' => 'Delete Job Assign Team',

            'operational.job-assign-material-issues' => 'Access Job Assign Material Issues',
            'operational.job-assign-material-issues.create' => 'Create Job Assign Material Issues',
            'operational.job-assign-material-issues.update' => 'Update Job Assign Material Issues',
            'operational.job-assign-material-issues.delete' => 'Delete Job Assign Material Issues',

            'operational.inventory-issuings' => 'Access Inventory Issuings',
            'operational.inventory-issuings.create' => 'Create Inventory Issuings',
            'operational.inventory-issuings.update' => 'Update Inventory Issuings',
            'operational.inventory-issuings.delete' => 'Delete Inventory Issuings',

            'operational.master-rooms' => 'Access Master Rooms',
            'operational.master-rooms.create' => 'Create Master Rooms',
            'operational.master-rooms.update' => 'Update Master Rooms',
            'operational.master-rooms.delete' => 'Delete Master Rooms',

            'operational.master-buildings' => 'Access Master Buildings',
            'operational.master-buildings.create' => 'Create Master Buildings',
            'operational.master-buildings.update' => 'Update Master Buildings',
            'operational.master-buildings.delete' => 'Delete Master Buildings',

            'operational.master-team' => 'Access Master Team',
            'operational.master-team.create' => 'Create Master Team',
            'operational.master-team.update' => 'Update Master Team',
            'operational.master-team.delete' => 'Delete Master Team',

            'operational.room-rental-units' => 'Access Room Rental Units',
            'operational.room-rental-units.create' => 'Create Room Rental Units',
            'operational.room-rental-units.update' => 'Update Room Rental Units',
            'operational.room-rental-units.delete' => 'Delete Room Rental Units',

            // Finance Module Permissions
            'finance.invoices' => 'Access Invoices',
            'finance.invoices.create' => 'Create Invoices',
            'finance.invoices.update' => 'Update Invoices',
            'finance.invoices.delete' => 'Delete Invoices',

            'finance.invoice-follow-ups' => 'Access Invoice Follow Ups',
            'finance.invoice-follow-ups.create' => 'Create Invoice Follow Ups',
            'finance.invoice-follow-ups.update' => 'Update Invoice Follow Ups',
            'finance.invoice-follow-ups.delete' => 'Delete Invoice Follow Ups',

            'finance.bank-receipts' => 'Access Bank Receipts',
            'finance.bank-receipts.create' => 'Create Bank Receipts',
            'finance.bank-receipts.update' => 'Update Bank Receipts',
            'finance.bank-receipts.delete' => 'Delete Bank Receipts',

            'finance.virtual-account-imports' => 'Access Virtual Account Imports',
            'finance.virtual-account-exports' => 'Access Virtual Account Exports',
            'finance.tax-file-imports' => 'Access Tax File Imports',
            'finance.tax-file-exports' => 'Access Tax File Exports',
            'finance.faktur-pajak' => 'Access Faktur Pajak',
            'finance.faktur-pajak.create' => 'Create Faktur Pajak',
            'finance.faktur-pajak.update' => 'Update Faktur Pajak',
            'finance.faktur-pajak.delete' => 'Delete Faktur Pajak',

            'finance.tax-settings' => 'Access Tax Settings',
            'finance.tax-codes' => 'Access Tax Codes',
            'finance.tax-codes.view' => 'View Tax Codes',
            'finance.tax-codes.edit' => 'Edit Tax Codes',
            'finance.commission-levels' => 'Access Commission Levels',
            'finance.commission-levels.create' => 'Create Commission Levels',
            'finance.commission-levels.update' => 'Update Commission Levels',
            'finance.commission-levels.delete' => 'Delete Commission Levels',

            'finance.marketing-levels' => 'Access Marketing Levels',
            'finance.marketing-levels.create' => 'Create Marketing Levels',
            'finance.marketing-levels.update' => 'Update Marketing Levels',
            'finance.marketing-levels.delete' => 'Delete Marketing Levels',

            'finance.cr-variables' => 'Access CR Variables',
            'finance.cr-variables.create' => 'Create CR Variables',
            'finance.cr-variables.update' => 'Update CR Variables',
            'finance.cr-variables.delete' => 'Delete CR Variables',

            'finance.marketing-targets' => 'Access Marketing Targets',
            'finance.marketing-targets.create' => 'Create Marketing Targets',
            'finance.marketing-targets.update' => 'Update Marketing Targets',
            'finance.marketing-targets.delete' => 'Delete Marketing Targets',

            'finance.renewal-contract-assignments' => 'Access Renewal Contract Assignments',
            'finance.commission-transfers' => 'Access Commission Transfers',
            'finance.commissions' => 'Access Commission System',
            'finance.achievements' => 'Access Achievements',
            'finance.achievements.create' => 'Create Achievements',
            'finance.achievements.update' => 'Update Achievements',
            'finance.achievements.delete' => 'Delete Achievements',

            'finance.achievement-periods' => 'Access Achievement Periods',
            'finance.achievement-periods.create' => 'Create Achievement Periods',
            'finance.achievement-periods.update' => 'Update Achievement Periods',
            'finance.achievement-periods.delete' => 'Delete Achievement Periods',

            'finance.commission-payments' => 'Access Commission Payments',
            'finance.commission-payments.create' => 'Create Commission Payments',
            'finance.commission-payments.update' => 'Update Commission Payments',
            'finance.commission-payments.delete' => 'Delete Commission Payments',

            // Warehouse Module Permissions
            'warehouse.warehouses' => 'Access Warehouses',
            'warehouse.warehouses.create' => 'Create Warehouses',
            'warehouse.warehouses.update' => 'Update Warehouses',
            'warehouse.warehouses.delete' => 'Delete Warehouses',

            'warehouse.product-structure' => 'Access Product Structure',
            'warehouse.product-types' => 'Access Product Types',
            'warehouse.product-types.create' => 'Create Product Types',
            'warehouse.product-types.update' => 'Update Product Types',
            'warehouse.product-types.delete' => 'Delete Product Types',

            'warehouse.master-products' => 'Access Master Products',
            'warehouse.master-products.create' => 'Create Master Products',
            'warehouse.master-products.update' => 'Update Master Products',
            'warehouse.master-products.delete' => 'Delete Master Products',

            'warehouse.master-rentals' => 'Access Master Rentals',
            'warehouse.master-rentals.create' => 'Create Master Rentals',
            'warehouse.master-rentals.update' => 'Update Master Rentals',
            'warehouse.master-rentals.delete' => 'Delete Master Rentals',

            'warehouse.inventory-issuings' => 'Access Inventory Issuings',
            'warehouse.inventory-issuings.create' => 'Create Inventory Issuings',
            'warehouse.inventory-issuings.update' => 'Update Inventory Issuings',
            'warehouse.inventory-issuings.delete' => 'Delete Inventory Issuings',

            'warehouse.inventory-receivings' => 'Access Inventory Receivings',
            'warehouse.inventory-receivings.create' => 'Create Inventory Receivings',
            'warehouse.inventory-receivings.update' => 'Update Inventory Receivings',
            'warehouse.inventory-receivings.delete' => 'Delete Inventory Receivings',

            'warehouse.inventory-requests' => 'Access Inventory Requests',
            'warehouse.inventory-requests.create' => 'Create Inventory Requests',
            'warehouse.inventory-requests.update' => 'Update Inventory Requests',
            'warehouse.inventory-requests.delete' => 'Delete Inventory Requests',

            'warehouse.inventory-transfers' => 'Access Inventory Transfers',
            'warehouse.inventory-transfers.view' => 'View Inventory Transfers',
            'warehouse.inventory-transfers.create' => 'Create Inventory Transfers',
            'warehouse.inventory-transfers.update' => 'Update Inventory Transfers',
            'warehouse.inventory-transfers.delete' => 'Delete Draft Inventory Transfers',
            'warehouse.inventory-transfers.submit' => 'Submit Inventory Transfers for Approval',
            'warehouse.inventory-transfers.approve' => 'Approve Inventory Transfers at Central Office',
            'warehouse.inventory-transfers.reject' => 'Reject Inventory Transfers at Central Office',
            'warehouse.inventory-transfers.transfer' => 'Mark Inventory Transfers as Transferred',
            'warehouse.inventory-transfers.receive' => 'Mark Inventory Transfers as Received',

            'warehouse.stock-opnames' => 'Access Stock Opnames',
            'warehouse.stock-opnames.create' => 'Create Stock Opnames',
            'warehouse.stock-opnames.update' => 'Update Stock Opnames',
            'warehouse.stock-opnames.delete' => 'Delete Stock Opnames',
            'warehouse.stock-opnames.approve' => 'Approve/Unpost Stock Opnames',
            'warehouse.stock-opnames.view-system-stock' => 'View System Stock in Stock Opnames',

            'warehouse.stock-adjustments' => 'Access Stock Adjustments',
            'warehouse.stock-adjustments.create' => 'Create Stock Adjustments',
            'warehouse.stock-adjustments.update' => 'Update Stock Adjustments',
            'warehouse.stock-adjustments.delete' => 'Delete Stock Adjustments',
            'warehouse.stock-adjustments.rollback' => 'Rollback Approved Stock Adjustments',

            'warehouse.serial-numbers' => 'Access Check Serial Number',
            'warehouse.unit-on-walls' => 'Access Unit On Wall',

            // System Module Permissions
            'system.departments' => 'Access Master Department',
            'system.departments.create' => 'Create Master Department',
            'system.departments.update' => 'Update Master Department',
            'system.departments.delete' => 'Delete Master Department',

            'system.users' => 'Access Master Users',
            'system.users.create' => 'Create Master Users',
            'system.users.update' => 'Update Master Users',
            'system.users.delete' => 'Delete Master Users',

            'system.roles' => 'Access Master Roles',
            'system.roles.create' => 'Create Master Roles',
            'system.roles.update' => 'Update Master Roles',
            'system.roles.delete' => 'Delete Master Roles',

            'system.access-control' => 'Access Hirarki Data',
            'system.access-control.create' => 'Create Hirarki Data',
            'system.access-control.update' => 'Update Hirarki Data',
            'system.access-control.delete' => 'Delete Hirarki Data',

            'system.provinces' => 'Access Master Location',
            'system.provinces.create' => 'Create Master Location',
            'system.provinces.update' => 'Update Master Location',
            'system.provinces.delete' => 'Delete Master Location',

            'system.audit-trails' => 'Access Audit Trails',
            'system.backup-restore' => 'Access Backup (Import/Export)',
            'system.backup-restore.template' => 'Download Backup Template',
            'system.backup-restore.export' => 'Export Backup Module Data',
            'system.backup-restore.import' => 'Import Backup Module Data',
            'system.backup-restore.delete' => 'Delete Backup Module Data',
            'system.salutations' => 'Access Master Salutation',
            'system.salutations.create' => 'Create Master Salutation',
            'system.salutations.update' => 'Update Master Salutation',
            'system.salutations.delete' => 'Delete Master Salutation',
            'system.master-term-of-payments' => 'Access Master Term of Payment',
            'system.master-term-of-payments.create' => 'Create Master Term of Payment',
            'system.master-term-of-payments.update' => 'Update Master Term of Payment',
            'system.master-term-of-payments.delete' => 'Delete Master Term of Payment',

            // Company Module Permissions
            'company.branches' => 'Access Master Branch',
            'company.branches.create' => 'Create Master Branch',
            'company.branches.update' => 'Update Master Branch',
            'company.branches.delete' => 'Delete Master Branch',

            'company.positions' => 'Access Master Position',
            'company.positions.create' => 'Create Master Position',
            'company.positions.update' => 'Update Master Position',
            'company.positions.delete' => 'Delete Master Position',

            'company.master-options' => 'Access Master Options',
            'company.master-banks' => 'Access Master Bank',
            'company.master-banks.create' => 'Create Master Bank',
            'company.master-banks.update' => 'Update Master Bank',
            'company.master-banks.delete' => 'Delete Master Bank',

            'company.bank-payments' => 'Access Bank Payment',
            'company.bank-payments.create' => 'Create Bank Payment',
            'company.bank-payments.update' => 'Update Bank Payment',
            'company.bank-payments.delete' => 'Delete Bank Payment',

            'company.master-price-slabs' => 'Access Master Price Slab',
            'company.master-price-slabs.create' => 'Create Master Price Slab',
            'company.master-price-slabs.update' => 'Update Master Price Slab',
            'company.master-price-slabs.delete' => 'Delete Master Price Slab',

            'company.companies' => 'Access Master Company',
            'company.companies.create' => 'Create Master Company',
            'company.companies.update' => 'Update Master Company',
            'company.companies.delete' => 'Delete Master Company',

            'company.company-virtual-accounts' => 'Access Company Virtual Account',
            'company.company-virtual-accounts.create' => 'Create Company Virtual Account',
            'company.company-virtual-accounts.update' => 'Update Company Virtual Account',
            'company.company-virtual-accounts.delete' => 'Delete Company Virtual Account',

            // Reports Module Permissions
            'reports.warehouse' => 'Access Warehouse Report',
            'reports.operational' => 'Access Operational Report',
            'reports.marketing' => 'Access Marketing Report',
            'reports.finance' => 'Access Finance Report',
        ];

        // Create all permissions
        foreach ($allPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'system_reserved' => true,
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('All module permissions created successfully!');

        // Assign permissions to roles based on role name
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles based on role name
     */
    private function assignPermissionsToRoles(): void
    {
        // Get all roles
        $allRoles = Role::where('is_active', true)->get();

        // Marketing Role Permissions
        $marketingRoles = Role::where('name', 'like', 'Marketing%')->get();
        foreach ($marketingRoles as $marketingRole) {
            $marketingPermissions = [
                'marketing.dashboard',
                'marketing.commissions.dashboard',
                'marketing.pipeline',
                'marketing.surveys',
                'marketing.quotations',
                'marketing.contracts',
                'marketing.contract-assigned',
                'marketing.contract-switchings',
                'marketing.job-advices',
                'marketing.lost-unit-reports',
                'marketing.customers',
                'marketing.customer-contacts',
                'marketing.customer-taxes',
                'marketing.customer-types',
                'marketing.stock-view',
                'marketing.stock-view.view',
                'company.bank-payments', // Marketing needs Bank Payment access
                'operational.master-rooms', // Marketing needs Master Room access
                'operational.master-buildings', // Marketing needs Master Building access
                'company.company-virtual-accounts', // Marketing needs Company Virtual Account access
                'system.salutations', // Marketing needs Master Salutation access
            ];

            $permissionIds = Permission::whereIn('name', $marketingPermissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $marketingRole->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'role_id' => $marketingRole->id,
                        'permission_id' => $permissionId,
                    ]
                );
            }
            $this->command->info("Marketing permissions assigned to {$marketingRole->name}");
        }

        // Operational Role Permissions
        $operationalRoles = Role::where('name', 'like', 'Operational%')->get();
        foreach ($operationalRoles as $operationalRole) {
            $operationalPermissions = [
                'operational.job-schedules',
                'operational.job-assign',
                'operational.job-assign-team',
                'operational.job-assign-material-issues',
                'operational.inventory-issuings',
                'operational.master-rooms',
                'operational.master-buildings',
                'operational.master-team',
                'operational.room-rental-units',
                'reports.operational',
            ];

            $permissionIds = Permission::whereIn('name', $operationalPermissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $operationalRole->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'role_id' => $operationalRole->id,
                        'permission_id' => $permissionId,
                    ]
                );
            }
            $this->command->info("Operational permissions assigned to {$operationalRole->name}");
        }

        // Finance Role Permissions
        $financeRoles = Role::where('name', 'like', 'Finance%')->get();
        foreach ($financeRoles as $financeRole) {
            $financePermissions = [
                'finance.invoices',
                'finance.invoice-follow-ups',
                'finance.bank-receipts',
                'finance.virtual-account-imports',
                'finance.virtual-account-exports',
                'finance.tax-file-imports',
                'finance.tax-file-exports',
                'finance.faktur-pajak',
                'finance.tax-settings',
                'finance.tax-codes',
                'finance.tax-codes.view',
                'finance.tax-codes.edit',
                'finance.commission-levels',
                'finance.marketing-levels',
                'finance.cr-variables',
                'finance.marketing-targets',
                'finance.renewal-contract-assignments',
                'finance.commission-transfers',
                'finance.commissions',
                'finance.achievements',
                'finance.achievement-periods',
                'finance.commission-payments',
                'reports.finance',
            ];

            $permissionIds = Permission::whereIn('name', $financePermissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $financeRole->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'role_id' => $financeRole->id,
                        'permission_id' => $permissionId,
                    ]
                );
            }
            $this->command->info("Finance permissions assigned to {$financeRole->name}");
        }

        // Warehouse Role Permissions
        $warehouseRoles = Role::where('name', 'like', 'Warehouse%')->get();
        foreach ($warehouseRoles as $warehouseRole) {
            $warehousePermissions = [
                'warehouse.warehouses',
                'warehouse.product-structure',
                'warehouse.product-types',
                'warehouse.master-products',
                'warehouse.master-rentals',
                'warehouse.inventory-issuings',
                'warehouse.inventory-receivings',
                'warehouse.inventory-requests',
                'warehouse.inventory-transfers',
                'warehouse.inventory-transfers.view',
                'warehouse.inventory-transfers.create',
                'warehouse.inventory-transfers.update',
                'warehouse.inventory-transfers.delete',
                'warehouse.inventory-transfers.submit',
                'warehouse.inventory-transfers.transfer',
                'warehouse.inventory-transfers.receive',
                'warehouse.stock-opnames',
                'warehouse.stock-adjustments',
                'warehouse.serial-numbers',
                'warehouse.unit-on-walls',
                'reports.warehouse',
            ];

            $permissionIds = Permission::whereIn('name', $warehousePermissions)->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $warehouseRole->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'role_id' => $warehouseRole->id,
                        'permission_id' => $permissionId,
                    ]
                );
            }
            $this->command->info("Warehouse permissions assigned to {$warehouseRole->name}");
        }

        // Management/Admin Role Permissions (All permissions)
        $managementRoles = Role::where('name', 'like', 'Management%')
            ->orWhere('name', 'like', 'Admin%')
            ->orWhere('name', 'Admin')
            ->get();

        foreach ($managementRoles as $managementRole) {
            $allPermissionIds = Permission::where('is_active', true)->pluck('id');
            foreach ($allPermissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $managementRole->id,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'role_id' => $managementRole->id,
                        'permission_id' => $permissionId,
                    ]
                );
            }
            $this->command->info("All permissions assigned to {$managementRole->name}");
        }
    }
}
