<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsolidatePermissionsSeeder extends Seeder
{
    public function run()
    {
        // 1. Define canonical module mapping for resources
        $resourceToModule = [
            'surveys' => 'marketing',
            'quotations' => 'marketing',
            'contracts' => 'marketing',
            'contract-terminations' => 'marketing',
            'contract-assigned' => 'marketing',
            'contract-switchings' => 'marketing',
            'aroma-changes' => 'marketing',
            'job-advices' => 'marketing',
            'lost-unit-reports' => 'marketing',
            'pipeline' => 'marketing',
            'projects' => 'marketing',
            'customers' => 'marketing',
            'customer-contacts' => 'marketing',
            'customer-taxes' => 'marketing',
            'customer-types' => 'marketing',
            'salutations' => 'marketing',
            'buildings' => 'marketing',
            'master-buildings' => 'marketing',
            'job-schedules' => 'operational',
            'job-assign' => 'operational',
            'job-assign-material-issues' => 'operational',
            'teams' => 'operational',
            'master-rooms' => 'operational',
            'master-team' => 'operational',
            'room-rental-units' => 'operational',
            'job-schedules-approve-ba' => 'operational',
            'job-schedules-approve-material-return' => 'operational',
            'invoices' => 'finance',
            'invoice-follow-ups' => 'finance',
            'tax-file-imports' => 'finance',
            'tax-file-exports' => 'finance',
            'tax-settings' => 'finance',
            'tax-codes' => 'finance',
            'faktur-pajak' => 'finance',
            'billing-schedules' => 'finance',
            'payments' => 'finance',
            'approvals' => 'finance',
            'commissions' => 'finance',
            'commission-levels' => 'finance',
            'commission-payments' => 'finance',
            'commission-transfers' => 'finance',
            'marketing-levels' => 'finance',
            'marketing-targets' => 'finance',
            'renewal-contract-assignments' => 'finance',
            'achievements' => 'finance',
            'achievement-periods' => 'finance',
            'cr-variables' => 'finance',
            'warehouses' => 'warehouse',
            'product-structure' => 'warehouse',
            'product-types' => 'warehouse',
            'brand-variants' => 'warehouse',
            'master-products' => 'warehouse',
            'master-rentals' => 'warehouse',
            'inventory-issuings' => 'warehouse',
            'inventory-receivings' => 'warehouse',
            'inventory-requests' => 'warehouse',
            'stock-opnames' => 'warehouse',
            'stock-adjustments' => 'warehouse',
            'serial-numbers' => 'warehouse',
            'unit-on-walls' => 'warehouse',
            'master-banks' => 'company',
            'bank-payments' => 'company',
            'master-price-slabs' => 'company',
            'companies' => 'company',
            'branches' => 'company',
            'company-virtual-accounts' => 'company',
            'positions' => 'company',
            'departments' => 'system',
            'users' => 'system',
            'roles' => 'system',
            'access-control' => 'system',
            'access-management' => 'system',
            'provinces' => 'system',
            'audit-trails' => 'system',
            'master-options' => 'other',
            'settings' => 'other',
            'reports' => 'report',
            'report' => 'report',
            'dashboard' => 'marketing',
        ];

        // 2. Singular to Plural Mapping (Standardization)
        $singularToPlural = [
            'survey' => 'surveys',
            'quotation' => 'quotations',
            'contract' => 'contracts',
            'contract-termination' => 'contract-terminations',
            'contract-switching' => 'contract-switchings',
            'aroma-change' => 'aroma-changes',
            'job-advice' => 'job-advices',
            'job-schedule' => 'job-schedules',
            'job-assign-material-issue' => 'job-assign-material-issues',
            'team' => 'teams',
            'master-room' => 'master-rooms',
            'invoice' => 'invoices',
            'warehouse' => 'warehouses',
            'master-product' => 'master-products',
            'master-rental' => 'master-rentals',
            'product-type' => 'product-types',
            'brand-variant' => 'brand-variants',
            'inventory-issuing' => 'inventory-issuings',
            'inventory-receiving' => 'inventory-receivings',
            'inventory-request' => 'inventory-requests',
            'stock-opname' => 'stock-opnames',
            'stock-adjustment' => 'stock-adjustments',
            'serial-number' => 'serial-numbers',
            'unit-on-wall' => 'unit-on-walls',
            'branch' => 'branches',
            'master-bank' => 'master-banks',
            'bank-payment' => 'bank-payments',
            'master-price-slab' => 'master-price-slabs',
            'company' => 'companies',
            'department' => 'departments',
            'user' => 'users',
            'role' => 'roles',
            'province' => 'provinces',
            'audit-trail' => 'audit-trails',
        ];

        $actionKeywords = ['create', 'update', 'edit', 'delete', 'remove', 'view', 'read', 'show', 'add', 'download', 'print', 'approve', 'dashboard', 'ubah', 'tambah', 'lihat', 'hapus', 'unduh', 'cetak', 'setuju', 'checkbox'];

        $allPermissions = Permission::all();
        $processedCount = 0;
        $deletedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($allPermissions as $permission) {
                $name = $permission->name;
                $parts = explode('.', $name);
                
                $module = '';
                $resource = '';
                $action = 'view';

                if (count($parts) >= 3) {
                    $action = strtolower(end($parts));
                    if (strtolower($parts[0]) === 'other') {
                        $remaining = array_slice($parts, 1, -1);
                        $found = false;
                        foreach ($remaining as $r) {
                            $r_std = $singularToPlural[strtolower($r)] ?? strtolower($r);
                            if (isset($resourceToModule[$r_std])) {
                                $module = $resourceToModule[$r_std];
                                $resource = implode('.', $remaining);
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $module = 'other';
                            $resource = implode('.', $remaining);
                        }
                    } else {
                        $module = strtolower($parts[0]);
                        $resource = implode('.', array_slice($parts, 1, -1));
                    }
                } elseif (count($parts) == 2) {
                    $first = strtolower($parts[0]);
                    $second = strtolower($parts[1]);
                    if (in_array($second, $actionKeywords)) {
                        $resource = $first;
                        $action = $second;
                    } else {
                        $module = $first;
                        $resource = $second;
                        $action = 'view';
                    }
                } else {
                    $resource = strtolower($name);
                    $action = 'view';
                }

                // Apply standardization
                $resource = $singularToPlural[$resource] ?? $resource;

                $canonicalModule = $resourceToModule[$resource] ?? $module;
                if ($canonicalModule === '') $canonicalModule = 'other';
                if (str_contains($resource, 'master-options')) $canonicalModule = 'other';

                $canonicalAction = $action;
                if (in_array($action, ['edit', 'ubah'])) $canonicalAction = 'update';
                if (in_array($action, ['add', 'tambah'])) $canonicalAction = 'create';
                if (in_array($action, ['show', 'lihat', 'read', 'checkbox'])) $canonicalAction = 'view';
                if (in_array($action, ['remove', 'hapus'])) $canonicalAction = 'delete';
                if ($action === 'unduh') $canonicalAction = 'download';
                if ($action === 'cetak') $canonicalAction = 'print';
                if ($action === 'setuju') $canonicalAction = 'approve';

                $canonicalName = "{$canonicalModule}.{$resource}.{$canonicalAction}";
                
                if ($canonicalName !== $name) {
                    $this->command->info("Consolidating: {$name} -> {$canonicalName}");
                    $canonicalPermission = Permission::firstOrCreate(
                        ['name' => $canonicalName],
                        ['description' => $permission->description, 'is_active' => true, 'system_reserved' => false]
                    );

                    $roleIds = DB::table('role_permissions')->where('permission_id', $permission->id)->pluck('role_id');
                    foreach ($roleIds as $roleId) {
                        DB::table('role_permissions')->updateOrInsert(
                            ['role_id' => $roleId, 'permission_id' => $canonicalPermission->id],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                    }

                    if ($permission->id !== $canonicalPermission->id) {
                        DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
                        $permission->delete();
                        $deletedCount++;
                    }
                    $processedCount++;
                }
            }

            // Weird names cleanup
            $weirdNames = [
                'contractNet_approved' => 'marketing.contracts.approve',
                'Company Management' => 'company.companies.view',
                'Finance Management' => 'finance.invoices.view',
                'Marketing Management' => 'marketing.dashboard.view',
                'Operational Management' => 'operational.job-schedules.view',
            ];
            foreach ($weirdNames as $old => $new) {
                $oldPerm = Permission::where('name', $old)->first();
                if ($oldPerm) {
                    $newPerm = Permission::firstOrCreate(['name' => $new], ['description' => 'Cleaned up permission', 'is_active' => true]);
                    DB::table('role_permissions')->where('permission_id', $oldPerm->id)->update(['permission_id' => $newPerm->id]);
                    $oldPerm->delete();
                    $deletedCount++;
                }
            }

            DB::commit();
            $this->command->info("Successfully processed {$processedCount} permissions and deleted {$deletedCount} duplicates.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error: " . $e->getMessage());
            throw $e;
        }
    }
}
