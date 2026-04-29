<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VariantChangePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates permissions for variant change in Material Issue:
     * - material-issues.variant.change: Can request variant change (all roles)
     * - material-issues.variant.approve: Can approve variant change when different (managers only)
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'material-issues.variant.change',
                'description' => 'Can request variant change in Material Issue',
                'system_reserved' => false,
                'is_active' => true,
            ],
            [
                'name' => 'material-issues.variant.approve',
                'description' => 'Can approve variant change when variant is different (Manager+)',
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

        $this->command->info('Variant change permissions created successfully!');

        // Assign approve permission to non-staff roles (admin, super_admin, manager, gm, ceo)
        $approverRoles = Role::whereIn('name', [
            'admin', 
            'super_admin', 
            'manager',
            'gm',
            'general_manager',
            'ceo',
            'director'
        ])->get();

        $approvePermission = Permission::where('name', 'material-issues.variant.approve')->first();
        
        if ($approvePermission) {
            foreach ($approverRoles as $role) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $role->id,
                        'permission_id' => $approvePermission->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $this->command->info("Assigned variant approve permission to role: {$role->name}");
            }
        }

        // Assign change permission to all active roles
        $allRoles = Role::where('is_active', true)->get();
        $changePermission = Permission::where('name', 'material-issues.variant.change')->first();
        
        if ($changePermission) {
            foreach ($allRoles as $role) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $role->id,
                        'permission_id' => $changePermission->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
            $this->command->info("Assigned variant change permission to all active roles");
        }
    }
}
