<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class UpdateAdminManagerMultiLoginSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Update all users with Administrator or Management Manager roles to have multi_login = true
     */
    public function run(): void
    {
        $this->command->info('Updating multi_login for Administrator and Management Manager users...');

        // Get role IDs for Administrator/Admin and roles containing "Management Manager"
        $adminRoles = Role::where(function($query) {
            $query->where('name', 'Administrator')
                  ->orWhere('name', 'Admin')
                  ->orWhere('name', 'super_admin')
                  ->orWhere('name', 'administrator');
        })->get();

        $managementManagerRoles = Role::where('name', 'like', '%Management Manager%')->get();

        $targetRoleIds = [];
        
        foreach ($adminRoles as $role) {
            $targetRoleIds[] = $role->id;
        }
        
        foreach ($managementManagerRoles as $role) {
            $targetRoleIds[] = $role->id;
        }

        if (empty($targetRoleIds)) {
            $this->command->warn('No matching roles found. Please check your roles table.');
        } else {
            // Get user IDs that have these roles
            $userIds = DB::table('user_roles')
                ->whereIn('role_id', $targetRoleIds)
                ->distinct()
                ->pluck('user_id')
                ->toArray();

            if (!empty($userIds)) {
                // Update multi_login to true for these users
                $updated = User::whereIn('id', $userIds)
                    ->update(['multi_login' => true]);

                $this->command->info("Updated {$updated} users with multi_login = true based on roles relationship.");
            } else {
                $this->command->warn('No users found with Administrator or Management Manager roles.');
            }
        }

        // Also check users with roles column (backward compatibility) - exact match for "Management Manager"
        $usersWithStringRoles = User::where(function($query) {
            $query->where('roles', 'like', '%Administrator%')
                  ->orWhere('roles', 'like', '%Admin%')
                  ->orWhere('roles', 'like', '%Management Manager%'); // Exact phrase "Management Manager"
        })
        ->where('multi_login', false)
        ->update(['multi_login' => true]);

        $this->command->info("Updated {$usersWithStringRoles} additional users based on roles column.");
        $this->command->info('Done!');
    }
}
