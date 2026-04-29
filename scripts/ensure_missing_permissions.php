<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$dryRun = in_array('--dry-run', $argv, true);

$permissions = [
    'operational.job-schedules.ba-date' => 'Edit Job Schedule BA Date',
    'operational.job-schedules.ba-date.update' => 'Update Job Schedule BA Date',
    'operational.job-schedules.approve-ba' => 'Approve Job Schedule BA Files',
    'operational.job-schedules.approve-ba.view' => 'View Approve Job Schedule BA Files Action',
    'operational.job-schedules.approve-material-return' => 'Approve Job Schedule Material Return',
    'operational.job-schedules.approve-material-return.view' => 'View Approve Job Schedule Material Return Action',
    'operational.job-schedules.approve' => 'Approve Job Schedules',
    'contracts.download' => 'Download Contracts',
    'contracts.print' => 'Print Contracts',
    'contracts.update' => 'Update Contracts',
    'marketing.contracts.download' => 'Download Contracts',
    'marketing.contracts.print' => 'Print Contracts',
    'marketing.contracts.view' => 'View Contracts',
    'marketing.contracts.update' => 'Update Contracts',
    'marketing.contracts.delete' => 'Delete Contracts',
    'marketing.contracts.approve' => 'Approve Contracts',
    'marketing.contract_files.create' => 'Create Contract Files',
    'marketing.contract_files.approve' => 'Approve Contract Files',
    'marketing.contract_files.delete' => 'Delete Contract Files',
    'marketing.contracts.target.create' => 'Create Contract Target',
    'marketing.contracts.target.update' => 'Update Contract Target',
    'marketing.contract-net.view' => 'View Contract Net',
    'marketing.contract-net.edit' => 'Edit Contract Net',
    'marketing.contract-net.approve' => 'Approve Contract Net',
    'contractNet_approved' => 'Legacy Contract Net Approval',
    'admin.delete' => 'Legacy Admin Delete',
];

$grantRoleNames = [
    'Administrator',
    'Super Admin',
    'Operational Manager',
    'Management Manager',
    'Manager',
];

echo ($dryRun ? '[DRY-RUN] ' : '[APPLY] ') . 'Ensuring ' . count($permissions) . " permission(s).\n";

$roles = Role::whereIn('name', $grantRoleNames)->orderBy('name')->get();
echo 'Grant target roles: ' . ($roles->pluck('name')->implode(', ') ?: '-') . "\n";

DB::transaction(function () use ($permissions, $roles, $dryRun) {
    foreach ($permissions as $name => $description) {
        $permission = Permission::where('name', $name)->first();
        $action = $permission ? 'exists' : 'create';

        echo "- {$name}: {$action}";

        if (!$dryRun && !$permission) {
            $permission = Permission::create([
                'name' => $name,
                'description' => $description,
                'is_active' => true,
                'system_reserved' => false,
            ]);
        }

        $missingRoles = [];
        if ($permission) {
            foreach ($roles as $role) {
                $hasPermission = DB::table('role_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (!$hasPermission) {
                    $missingRoles[] = $role->name;

                    if (!$dryRun) {
                        DB::table('role_permissions')->insert([
                            'role_id' => $role->id,
                            'permission_id' => $permission->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        } elseif ($dryRun) {
            $missingRoles = $roles->pluck('name')->all();
        }

        echo $missingRoles ? ' | grant to: ' . implode(', ', $missingRoles) : ' | role grants OK';
        echo "\n";
    }
});

echo $dryRun
    ? "Dry-run selesai. Jalankan tanpa --dry-run untuk apply.\n"
    : "Apply selesai.\n";
