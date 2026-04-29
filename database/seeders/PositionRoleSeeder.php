<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Role;
use App\Models\PositionRole;
use App\Models\User;

class PositionRoleSeeder extends Seeder
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

        // Get departments and roles
        $departments = Department::all();
        $roles = Role::all();

        if ($departments->isEmpty() || $roles->isEmpty()) {
            $this->command->error('No departments or roles found. Please run other seeders first.');
            return;
        }

        // Position-based role mapping
        $positionRoleMapping = [
            // Marketing Department
            'Marketing' => [
                'Manager' => 'Marketing',
                'Supervisor' => 'Marketing',
                'Staff' => 'Marketing',
                'VP Marketing' => 'Admin',
                'Senior Manager' => 'Marketing',
                'Junior Manager' => 'Marketing',
                'Team Lead' => 'Marketing',
                'Coordinator' => 'Marketing',
                'Specialist' => 'Marketing',
            ],
            // Finance Department
            'Finance' => [
                'Manager' => 'Finance',
                'Supervisor' => 'Finance',
                'Staff' => 'Finance',
                'CFO' => 'Admin',
                'VP Finance' => 'Admin',
                'Senior Manager' => 'Finance',
                'Junior Manager' => 'Finance',
                'Team Lead' => 'Finance',
                'Coordinator' => 'Finance',
                'Specialist' => 'Finance',
            ],
            // Warehouse Department
            'Warehouse' => [
                'Manager' => 'Warehouse',
                'Supervisor' => 'Warehouse',
                'Staff' => 'Warehouse',
                'Senior Manager' => 'Warehouse',
                'Junior Manager' => 'Warehouse',
                'Team Lead' => 'Warehouse',
                'Coordinator' => 'Warehouse',
                'Specialist' => 'Warehouse',
            ],
            // Operational Department
            'Operational' => [
                'Manager' => 'Supervisor',
                'Supervisor' => 'Supervisor',
                'Staff' => 'Technician',
                'VP Operations' => 'Admin',
                'Senior Manager' => 'Supervisor',
                'Junior Manager' => 'Supervisor',
                'Team Lead' => 'Supervisor',
                'Coordinator' => 'Technician',
                'Specialist' => 'Technician',
            ],
            // IT Department
            'IT' => [
                'Manager' => 'Admin',
                'Supervisor' => 'Admin',
                'Staff' => 'Admin',
                'CTO' => 'Admin',
                'Senior Manager' => 'Admin',
                'Junior Manager' => 'Admin',
                'Team Lead' => 'Admin',
                'Coordinator' => 'Admin',
                'Specialist' => 'Admin',
            ],
            // HR Department
            'HR' => [
                'Manager' => 'Admin',
                'Supervisor' => 'Admin',
                'Staff' => 'Admin',
                'VP HR' => 'Admin',
                'Senior Manager' => 'Admin',
                'Junior Manager' => 'Admin',
                'Team Lead' => 'Admin',
                'Coordinator' => 'Admin',
                'Specialist' => 'Admin',
            ],
            // Management
            'Management' => [
                'CEO' => 'Admin',
                'Director' => 'Admin',
                'VP Sales' => 'Admin',
                'VP Marketing' => 'Admin',
                'VP Operations' => 'Admin',
                'VP Finance' => 'Admin',
                'VP HR' => 'Admin',
            ],
        ];

        foreach ($departments as $department) {
            $departmentMappings = $positionRoleMapping[$department->name] ?? [];
            
            foreach ($departmentMappings as $position => $roleName) {
                $role = $roles->where('name', $roleName)->first();
                
                if ($role) {
                    PositionRole::updateOrCreate(
                        [
                            'department_id' => $department->id,
                            'position_name' => $position,
                            'role_id' => $role->id
                        ],
                        [
                            'is_active' => true
                        ]
                    );

                    $this->command->info("Mapped {$department->name} - {$position} to role {$role->name}");
                }
            }
        }

        $this->command->info('Position role mappings created successfully!');
    }
}
