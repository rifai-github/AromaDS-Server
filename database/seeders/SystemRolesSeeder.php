<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class SystemRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user for created_by and updated_by
        $firstUser = User::first();
        if (!$firstUser) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        // Create system roles
        $roles = [
            [
                'name' => 'Admin',
                'description' => 'System Administrator with full access',
                'created_by' => $firstUser->id,
                'updated_by' => $firstUser->id,
            ],
            [
                'name' => 'Marketing',
                'description' => 'Marketing staff with access to marketing modules',
                'created_by' => $firstUser->id,
                'updated_by' => $firstUser->id,
            ],
            [
                'name' => 'Technician',
                'description' => 'Field technician with operational access',
                'created_by' => $firstUser->id,
                'updated_by' => $firstUser->id,
            ],
            [
                'name' => 'Supervisor',
                'description' => 'Operational supervisor with management access',
                'created_by' => $firstUser->id,
                'updated_by' => $firstUser->id,
            ],
            [
                'name' => 'Finance',
                'description' => 'Finance staff with financial module access',
                'created_by' => $firstUser->id,
                'updated_by' => $firstUser->id,
            ],
            [
                'name' => 'Warehouse',
                'description' => 'Warehouse staff with inventory management access',
                'created_by' => $firstUser->id,
                'updated_by' => $firstUser->id,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        $this->command->info('System roles created successfully!');
    }
}
