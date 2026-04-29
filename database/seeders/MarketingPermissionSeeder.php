<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class MarketingPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create marketing permissions
        $marketingPermissions = [
            'branches.view' => 'View branches',
            'customers.view' => 'View customers',
            'customers.create' => 'Create customers',
            'customers.edit' => 'Edit customers',
            'bank-payments.view' => 'View bank payments', // Needed for contract wizard step 4 (billing group)
            'marketing.master-corporates.approve' => 'Approve master corporate prices',
        ];

        foreach ($marketingPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'system_reserved' => true,
                    'is_active' => true
                ]
            );
        }

        // Get marketing role
        $marketingRole = Role::where('name', 'Marketing')->first();
        
        if ($marketingRole) {
            // Assign permissions to marketing role
            $marketingPermissionIds = Permission::whereIn('name', array_keys($marketingPermissions))->pluck('id');
            $marketingRole->permissions()->syncWithoutDetaching($marketingPermissionIds);
            
            $this->command->info('Marketing permissions assigned to Marketing role');
        } else {
            $this->command->warn('Marketing role not found');
        }

        // Also assign to marketing users directly
        $marketingUsers = User::where('roles', 'Marketing')->get();
        
        foreach ($marketingUsers as $user) {
            $marketingPermissionIds = Permission::whereIn('name', array_keys($marketingPermissions))->pluck('id');
            $user->permissions()->syncWithoutDetaching($marketingPermissionIds);
        }
        
        if ($marketingUsers->count() > 0) {
            $this->command->info("Marketing permissions assigned to {$marketingUsers->count()} marketing users");
        }
    }
}
