<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class FixPermissionNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:fix-names {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix permission names from module.resource.action format to resource.action format';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }
        
        $this->info('Scanning permissions for format issues...');
        
        // Known modules that are incorrectly prefixed
        $modules = ['marketing', 'operational', 'warehouse', 'finance', 'system', 'company', 'hr', 'admin'];
        
        // Get all permissions
        $permissions = Permission::all();
        $fixedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($permissions as $permission) {
                $originalName = $permission->name;
                $parts = explode('.', $originalName);
                
                // Check if first part is a module name and there are 3+ parts
                // e.g., marketing.customers.view -> customers.view
                if (count($parts) >= 3 && in_array(strtolower($parts[0]), $modules)) {
                    // Remove the module prefix
                    $newName = implode('.', array_slice($parts, 1));
                    
                    // Check if the new name already exists
                    $existingPermission = Permission::where('name', $newName)
                        ->where('id', '!=', $permission->id)
                        ->first();
                    
                    if ($existingPermission) {
                        // Merge: Update role_permissions to point to existing permission
                        $this->warn("  ⚠️  {$originalName} -> {$newName} (MERGE - permission already exists)");
                        
                        if (!$dryRun) {
                            // Get all role_permissions pointing to the old permission
                            $oldRolePermissions = DB::table('role_permissions')
                                ->where('permission_id', $permission->id)
                                ->get();
                            
                            foreach ($oldRolePermissions as $rp) {
                                // Check if role already has the new permission
                                $exists = DB::table('role_permissions')
                                    ->where('role_id', $rp->role_id)
                                    ->where('permission_id', $existingPermission->id)
                                    ->exists();
                                
                                if (!$exists) {
                                    // Point to existing permission
                                    DB::table('role_permissions')
                                        ->where('id', $rp->id)
                                        ->update(['permission_id' => $existingPermission->id]);
                                } else {
                                    // Delete duplicate
                                    DB::table('role_permissions')
                                        ->where('id', $rp->id)
                                        ->delete();
                                }
                            }
                            
                            // Delete the old permission
                            $permission->delete();
                        }
                        
                        $fixedCount++;
                    } else {
                        // Simple rename
                        $this->info("  ✓ {$originalName} -> {$newName}");
                        
                        if (!$dryRun) {
                            $permission->name = $newName;
                            $permission->save();
                        }
                        
                        $fixedCount++;
                    }
                } elseif (count($parts) === 2 && in_array(strtolower($parts[0]), $modules)) {
                    // Handle case like marketing.customers -> customers.view
                    $resourceName = $parts[1];
                    $newName = "{$resourceName}.view";
                    
                    // Check if the new name already exists
                    $existingPermission = Permission::where('name', $newName)
                        ->where('id', '!=', $permission->id)
                        ->first();
                    
                    if ($existingPermission) {
                        $this->warn("  ⚠️  {$originalName} -> {$newName} (MERGE)");
                        
                        if (!$dryRun) {
                            // Similar merge logic as above
                            $oldRolePermissions = DB::table('role_permissions')
                                ->where('permission_id', $permission->id)
                                ->get();
                            
                            foreach ($oldRolePermissions as $rp) {
                                $exists = DB::table('role_permissions')
                                    ->where('role_id', $rp->role_id)
                                    ->where('permission_id', $existingPermission->id)
                                    ->exists();
                                
                                if (!$exists) {
                                    DB::table('role_permissions')
                                        ->where('id', $rp->id)
                                        ->update(['permission_id' => $existingPermission->id]);
                                } else {
                                    DB::table('role_permissions')
                                        ->where('id', $rp->id)
                                        ->delete();
                                }
                            }
                            
                            $permission->delete();
                        }
                        
                        $fixedCount++;
                    } else {
                        $this->info("  ✓ {$originalName} -> {$newName}");
                        
                        if (!$dryRun) {
                            $permission->name = $newName;
                            $permission->save();
                        }
                        
                        $fixedCount++;
                    }
                } else {
                    $skippedCount++;
                }
            }
            
            if (!$dryRun) {
                DB::commit();
                $this->newLine();
                $this->info("✅ Fixed {$fixedCount} permissions");
                $this->info("⏭️  Skipped {$skippedCount} permissions (already correct format)");
            } else {
                DB::rollBack();
                $this->newLine();
                $this->info("🔍 Would fix {$fixedCount} permissions");
                $this->info("⏭️  Would skip {$skippedCount} permissions");
                $this->newLine();
                $this->warn('Run without --dry-run to apply changes');
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
