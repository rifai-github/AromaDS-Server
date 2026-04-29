<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class EnsureCustomerViewPermission extends Command
{
    protected $signature = 'permissions:ensure-customer-view';
    protected $description = 'Ensure customers.view permission exists and is linked to Marketing Staff role';

    public function handle()
    {
        $this->info('Ensuring customers.view permission exists...');
        
        // Create or find the permission
        $permission = Permission::firstOrCreate(
            ['name' => 'customers.view'],
            [
                'description' => 'View Customers',
                'system_reserved' => false,
            ]
        );
        
        $this->info("Permission ID: {$permission->id}");
        
        // Find Marketing Staff role(s)
        $roles = Role::where('name', 'like', '%Marketing Staff%')->get();
        
        foreach ($roles as $role) {
            $this->info("Checking role: {$role->name} (ID: {$role->id})");
            
            // Check if already linked
            $linked = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->exists();
            
            if ($linked) {
                $this->info("  Already linked.");
            } else {
                DB::table('role_permissions')->insert([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("  Permission attached!");
            }
        }
        
        if ($roles->isEmpty()) {
            $this->warn('No Marketing Staff role found. Creating permission only.');
        }
        
        // Also ensure pipeline.view, pipeline.create exist for Marketing Staff
        $pipelinePermissions = ['pipeline.view', 'pipeline.create', 'pipeline.update', 'pipeline.delete'];
        
        foreach ($pipelinePermissions as $permName) {
            $perm = Permission::firstOrCreate(
                ['name' => $permName],
                [
                    'description' => ucfirst(str_replace('.', ' ', $permName)),
                    'system_reserved' => false,
                ]
            );
            
            $this->info("Ensuring {$permName} (ID: {$perm->id})...");
            
            foreach ($roles as $role) {
                $linked = DB::table('role_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $perm->id)
                    ->exists();
                
                if (!$linked) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $role->id,
                        'permission_id' => $perm->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $this->info("  -> Attached to {$role->name}");
                }
            }
        }
        
        $this->info('Done!');
        return 0;
    }
}
