<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Role;
use App\Models\DepartmentRole;
use App\Models\User;

class DepartmentRoleSeeder extends Seeder
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

        // Map departments to roles
        $departmentRoleMapping = [
            'Marketing' => 'Marketing',
            'Sales' => 'Marketing',
            'Operational' => 'Technician',
            'Technical' => 'Technician',
            'Finance' => 'Finance',
            'Accounting' => 'Finance',
            'Warehouse' => 'Warehouse',
            'Inventory' => 'Warehouse',
            'IT' => 'Admin',
            'System' => 'Admin',
            'HR' => 'Admin',
            'Management' => 'Admin',
        ];

        foreach ($departments as $department) {
            $roleName = $departmentRoleMapping[$department->name] ?? 'Admin';
            $role = $roles->where('name', $roleName)->first();

            if ($role) {
                DepartmentRole::updateOrCreate(
                    [
                        'department_id' => $department->id,
                        'role_id' => $role->id
                    ],
                    [
                        'is_active' => true
                    ]
                );

                $this->command->info("Mapped department '{$department->name}' to role '{$role->name}'");
            }
        }

        $this->command->info('Department role mappings created successfully!');
    }
}
