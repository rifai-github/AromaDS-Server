<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserAccessLevel;
use App\Models\UserLoginRestriction;

class AuditTrailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user
        $admin = User::where('username', 'admin')->first();
        
        if ($admin) {
            // Set hierarchical access for admin
            UserAccessLevel::create([
                'user_id' => $admin->id,
                'access_type' => 'hierarchical',
                'access_config' => [
                    'subordinates' => [], // Admin can access all
                    'level' => 'supervisor'
                ],
                'is_active' => true
            ]);

            // Set branch access for admin
            UserAccessLevel::create([
                'user_id' => $admin->id,
                'access_type' => 'branch',
                'access_config' => [
                    'allowed_branches' => [1, 2, 3] // All branches
                ],
                'is_active' => true
            ]);

            // Set login restrictions for admin (no restrictions)
            UserLoginRestriction::create([
                'user_id' => $admin->id,
                'start_time' => null,
                'end_time' => null,
                'allowed_days' => null,
                'idle_timeout' => 60, // 1 hour
                'is_active' => true
            ]);
        }

        // Create sample access levels for other users
        $users = User::where('username', '!=', 'admin')->limit(5)->get();
        
        foreach ($users as $user) {
            // Set peer access
            UserAccessLevel::create([
                'user_id' => $user->id,
                'access_type' => 'peer',
                'access_config' => [
                    'peer_users' => [$admin->id] // Can access admin's data
                ],
                'is_active' => true
            ]);

            // Set branch access (limited)
            UserAccessLevel::create([
                'user_id' => $user->id,
                'access_type' => 'branch',
                'access_config' => [
                    'allowed_branches' => [1] // Only HQ
                ],
                'is_active' => true
            ]);

            // Set login restrictions (business hours only)
            UserLoginRestriction::create([
                'user_id' => $user->id,
                'start_time' => '08:00',
                'end_time' => '17:00',
                'allowed_days' => [1, 2, 3, 4, 5], // Monday to Friday
                'idle_timeout' => 30, // 30 minutes
                'is_active' => true
            ]);
        }
    }
}
