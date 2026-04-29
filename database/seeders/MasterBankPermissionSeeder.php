<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\User;

class MasterBankPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create master bank permissions
        $masterBankPermissions = [
            'master-banks.view' => 'View master banks',
            'master-banks.create' => 'Create master banks',
            'master-banks.edit' => 'Edit master banks',
            'master-banks.delete' => 'Delete master banks',
        ];

        foreach ($masterBankPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'system_reserved' => true,
                    'is_active' => true
                ]
            );
        }

        // Assign master bank permissions to admin users
        $adminUsers = User::whereIn('roles', ['admin', 'super_admin'])->get();
        if ($adminUsers->isEmpty()) {
            // If no admin users found, assign to first user
            $adminUsers = User::take(1)->get();
        }

        foreach ($adminUsers as $adminUser) {
            $masterBankPermissionIds = Permission::whereIn('name', array_keys($masterBankPermissions))->pluck('id');
            $adminUser->permissions()->syncWithoutDetaching($masterBankPermissionIds);
        }

        $this->command->info('Master Bank permissions created and assigned successfully!');
    }
}
