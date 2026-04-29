<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class SeedPermissions extends Seeder
{
    public function run()
    {
        // define modules and their resources
        $structure = [
            'marketing' => [
                'dashboard' => ['view'],
                'pipeline' => ['view'],
                'surveys' => ['view', 'create', 'edit', 'delete'],
                'quotations' => ['view', 'create', 'edit', 'delete', 'approve', 'download', 'print'],
                'contracts' => ['view', 'create', 'edit', 'delete', 'download', 'print'],
                'projects' => ['view', 'create', 'edit', 'delete'],
                'job-schedules' => ['view'], 
                'customers' => ['view', 'create', 'edit', 'delete'],
                'customer-contacts' => ['view', 'create', 'edit', 'delete'],
                'customer-types' => ['view', 'create', 'edit', 'delete'],
                'contract-assigned' => ['view'],
                'contract-switchings' => ['view', 'create', 'edit', 'delete'],
                'contract-terminations' => ['view', 'create', 'edit', 'delete'],
                'job-advices' => ['view', 'create', 'edit', 'delete', 'download', 'print'],
                'lost-unit-reports' => ['view', 'create', 'download', 'print'],
            ],
            'operational' => [
                'dashboard' => ['view'],
                'job-schedules' => ['view', 'create', 'edit', 'delete', 'download', 'print'],
                'job-assign' => ['view', 'create', 'edit'],
                'master-buildings' => ['view', 'create', 'edit', 'delete'],
                'master-rooms' => ['view', 'create', 'edit', 'delete'],
                'master-team' => ['view', 'create', 'edit', 'delete'],
                'room-rental-units' => ['view', 'create', 'edit', 'delete'],
                'job-assign-material-issues' => ['view', 'create'],
            ],
            'warehouse' => [
                'warehouses' => ['view', 'create', 'edit', 'delete'],
                'product-structure' => ['view', 'create', 'edit', 'delete'],
                'product-types' => ['view', 'create', 'edit', 'delete'],
                'master-products' => ['view', 'create', 'edit', 'delete'],
                'master-rentals' => ['view', 'create', 'edit', 'delete'],
                'inventory-issuings' => ['view', 'create', 'edit', 'delete'],
                'inventory-receivings' => ['view', 'create', 'edit', 'delete'],
                'inventory-requests' => ['view', 'create', 'edit', 'delete'],
                'stock-opnames' => ['view', 'create', 'edit', 'delete'],
                'stock-adjustments' => ['view', 'create', 'edit', 'delete'],
                'serial-numbers' => ['view'],
                'unit-on-walls' => ['view'],
            ],
            'finance' => [
                'dashboard' => ['view'],
                'approvals' => ['view', 'approve'],
                'invoices' => ['view', 'create', 'edit', 'delete', 'download', 'print'],
                'payments' => ['view', 'create', 'edit', 'delete', 'download', 'print'],
                'billing-schedules' => ['view', 'create', 'edit'],
                'invoice-follow-ups' => ['view', 'create', 'edit'],
                'tax-file-imports' => ['view', 'create'],
                'tax-file-exports' => ['view', 'create'],
                'faktur-pajak' => ['view', 'create', 'edit'],
                'tax-settings' => ['view', 'edit'],
                'tax-codes' => ['view', 'edit'],
                'commission-levels' => ['view', 'create', 'edit', 'delete'],
                'marketing-levels' => ['view', 'create', 'edit', 'delete'],
                'cr-variables' => ['view', 'create', 'edit', 'delete'],
                'marketing-targets' => ['view', 'create', 'edit', 'delete'],
                'renewal-contract-assignments' => ['view', 'create', 'edit'],
                'commission-transfers' => ['view', 'create', 'edit'],
                'commissions' => ['view', 'create', 'edit'],
                'achievements' => ['view', 'create', 'edit'],
                'achievement-periods' => ['view', 'create', 'edit', 'delete'],
                'commission-payments' => ['view', 'create', 'edit'],
            ],
            'company' => [
                'branches' => ['view', 'create', 'edit', 'delete'],
                'positions' => ['view', 'create', 'edit', 'delete'], // System positions moved here in UI
                'master-options' => ['view', 'create', 'edit', 'delete'],
                'master-banks' => ['view', 'create', 'edit', 'delete'],
                'bank-payments' => ['view', 'create', 'edit', 'delete'],
                'master-price-slabs' => ['view', 'create', 'edit', 'delete'],
                'companies' => ['view', 'create', 'edit', 'delete'],
            ],
            'system' => [
                'users' => ['view', 'create', 'edit', 'delete'],
                'roles' => ['view', 'create', 'edit', 'delete'],
                'departments' => ['view', 'create', 'edit', 'delete'],
                'provinces' => ['view', 'create', 'edit', 'delete'],
                'access-control' => ['view', 'edit'],
                'audit-trails' => ['view'],
                'salutations' => ['view', 'create', 'edit', 'delete'],
                'emergency-contacts' => ['view', 'create', 'edit', 'delete'],
            ],
            'report' => [
                'marketing' => ['view', 'download', 'print'],
                'operational' => ['view', 'download', 'print'],
                'finance' => ['view', 'download', 'print'],
                'warehouse' => ['view', 'download', 'print'],
            ]
        ];

        DB::transaction(function () use ($structure) {
            foreach ($structure as $module => $resources) {
                foreach ($resources as $resource => $actions) {
                    foreach ($actions as $action) {
                        // For 'view' action, we also create the base menuItem permission (module.resource)
                        // This matches how app.blade.php checks for 'canAccessMenuItem'
                        if ($action === 'view') {
                            $name = "{$module}.{$resource}";
                            $this->createPermission($name, "View {$resource} menu");
                        }

                        // Standard CRUD permissions: module.resource.action
                        // e.g., marketing.customers.create
                        // Exception: 'view' can sometimes be redundant if the base menu item permission exists,
                        // but having explicit CRUD is safer for the Role Edit UI.
                        $name = "{$module}.{$resource}.{$action}";
                        $description = ucfirst($action) . " " . ucwords(str_replace('-', ' ', $resource));
                        $this->createPermission($name, $description);
                    }
                }
            }
        });
    }

    private function createPermission($name, $description)
    {
        Permission::firstOrCreate(
            ['name' => $name],
            [
                'description' => $description,
                'is_active' => true,
                'system_reserved' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
