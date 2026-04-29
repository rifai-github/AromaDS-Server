<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

class FixJobSchedulePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'operational.job-schedules.ba-date' => 'Edit Job Schedule BA Date',
            'operational.job-schedules.ba-date.update' => 'Update Job Schedule BA Date',
            'operational.job-schedules.approve-ba' => 'Approve BA Files',
            'operational.job-schedules.approve-ba.view' => 'View Approve BA Files Action',
            'operational.job-schedules.approve-material-return' => 'Approve Material Return',
            'operational.job-schedules.approve-material-return.view' => 'View Approve Material Return Action',
            'operational.job-schedules.approve' => 'Approve Job Schedules',
        ];

        foreach ($permissions as $name => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'is_active' => true, 'system_reserved' => false]
            );

            $this->command->info("Permission ensure created: {$name}");

            // Assign to Administrator and Operational Manager roles
            $roles = Role::whereIn('name', ['Administrator', 'Super Admin', 'Operational Manager'])->get();
            
            foreach ($roles as $role) {
                if (!$role->hasPermission($name)) {
                    $role->permissions()->attach($permission->id);
                    $this->command->info("Assigned {$name} to role {$role->name}");
                }
            }
        }
    }
}
