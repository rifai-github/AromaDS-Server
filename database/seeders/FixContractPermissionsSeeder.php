<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixContractPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Get permission IDs
        $downloadPermission = DB::table('permissions')->where('name', 'contracts.download')->first();
        $printPermission = DB::table('permissions')->where('name', 'contracts.print')->first();
        
        if (!$downloadPermission || !$printPermission) {
            $this->command->error('Contract permissions not found!');
            return;
        }
        
        // Get admin role
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        
        if (!$adminRole) {
            $this->command->error('Admin role not found!');
            return;
        }
        
        // Assign permissions to admin role
        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $adminRole->id,
                'permission_id' => $downloadPermission->id,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $adminRole->id,
                'permission_id' => $printPermission->id,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        $this->command->info('Contract permissions assigned to admin role successfully!');
        
        // Also assign to super_admin if exists
        $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();
        if ($superAdminRole) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $superAdminRole->id,
                    'permission_id' => $downloadPermission->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $superAdminRole->id,
                    'permission_id' => $printPermission->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            $this->command->info('Contract permissions assigned to super_admin role successfully!');
        }
    }
}
