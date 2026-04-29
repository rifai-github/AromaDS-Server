<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContractPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $permissions = [
            [
                'name' => 'contracts.download',
                'description' => 'Permission to download contract as PDF file',
                'system_reserved' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'contracts.print',
                'description' => 'Permission to print contract as PDF',
                'system_reserved' => 0,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Insert permissions
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Assign permissions to roles
        $rolePermissions = [
            'admin' => ['contracts.download', 'contracts.print'],
            'manager' => ['contracts.download', 'contracts.print'],
            'super_admin' => ['contracts.download', 'contracts.print'],
            'marketing' => ['contracts.download', 'contracts.print'], // Marketing staff can download/print contracts they created
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            
            if ($role) {
                foreach ($permissionNames as $permissionName) {
                    $permission = DB::table('permissions')->where('name', $permissionName)->first();
                    
                    if ($permission) {
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
                    }
                }
            }
        }

        $this->command->info('Contract permissions seeded successfully!');
    }
}
