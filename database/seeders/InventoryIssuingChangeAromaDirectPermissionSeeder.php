<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryIssuingChangeAromaDirectPermissionSeeder extends Seeder
{
    /**
     * Ensures 'warehouse.inventory-issuings.change-aroma-direct' exists and is assigned
     * only to the 'Management Manager' role. This permission gates the "Change Aroma"
     * shortcut on the Inventory Issuing detail page, which updates the contract's aroma
     * directly and bypasses the Marketing > Aroma Switching approval workflow — client
     * confirmed this must stay restricted to Top Management, not all Management roles.
     */
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'warehouse.inventory-issuings.change-aroma-direct'],
            ['description' => 'Change Aroma directly from Inventory Issuing (Top Management only)', 'is_active' => true]
        );

        $role = Role::where('name', 'Management Manager')->first();

        if (!$role) {
            $this->command?->warn("Role 'Management Manager' not found. Skipping permission assignment.");

            return;
        }

        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command?->info("Assigned warehouse.inventory-issuings.change-aroma-direct to role: {$role->name}");
    }
}
