<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AromaChangeApprovePermissionSeeder extends Seeder
{
    /**
     * Ensures 'marketing.aroma-changes.approve' exists and is assigned to manager-level
     * roles, so the grade-up approval gate in AromaChangeController::approve() has at
     * least one role that can act on it immediately after deploy.
     */
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'marketing.aroma-changes.approve'],
            ['description' => 'Approve Aroma Switching', 'is_active' => true]
        );

        $approverRoles = Role::where('name', 'like', '%Management%')
            ->orWhere('name', 'like', '%Manager%')
            ->get();

        foreach ($approverRoles as $role) {
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
            $this->command?->info("Assigned marketing.aroma-changes.approve to role: {$role->name}");
        }
    }
}
