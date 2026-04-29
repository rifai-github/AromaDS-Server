<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class AddMarketingPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Find the role "Role251212Mkt"
        $role = Role::where('name', 'Role251212Mkt')->first();
        
        if (!$role) {
            $this->command->error('❌ Role "Role251212Mkt" tidak ditemukan!');
            $this->command->info('💡 Mencoba mencari role dengan nama yang mirip...');
            
            // Try to find similar roles
            $similarRoles = Role::where('name', 'LIKE', '%Mkt%')
                ->orWhere('name', 'LIKE', '%Marketing%')
                ->get();
            
            if ($similarRoles->count() > 0) {
                $this->command->info('Ditemukan role berikut:');
                foreach ($similarRoles as $sr) {
                    $this->command->line("  - ID: {$sr->id}, Name: {$sr->name}");
                }
            }
            
            return;
        }
        
        $this->command->info("✅ Role ditemukan: {$role->name} (ID: {$role->id})");
        
        // Define permissions that need to be added
        $permissionsToAdd = [
            'marketing.contract_files.create',
            'company.bank-payments.view',
            'marketing.customer-taxes.view',
        ];
        
        $addedCount = 0;
        $alreadyExistsCount = 0;
        $notFoundPermissions = [];
        
        foreach ($permissionsToAdd as $permissionName) {
            // Find the permission
            $permission = Permission::where('name', $permissionName)->first();
            
            if (!$permission) {
                $notFoundPermissions[] = $permissionName;
                $this->command->warn("⚠️  Permission '{$permissionName}' tidak ditemukan di database");
                continue;
            }
            
            // Check if role already has this permission
            $exists = DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $permission->id)
                ->exists();
            
            if ($exists) {
                $this->command->line("ℹ️  Permission '{$permissionName}' sudah ada pada role");
                $alreadyExistsCount++;
                continue;
            }
            
            // Add permission to role
            DB::table('role_permissions')->insert([
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info("✅ Permission '{$permissionName}' berhasil ditambahkan");
            $addedCount++;
        }
        
        // Summary
        $this->command->newLine();
        $this->command->info('========== SUMMARY ==========');
        $this->command->info("Role: {$role->name}");
        $this->command->info("Permission ditambahkan: {$addedCount}");
        $this->command->info("Permission sudah ada: {$alreadyExistsCount}");
        
        if (count($notFoundPermissions) > 0) {
            $this->command->error("Permission tidak ditemukan: " . implode(', ', $notFoundPermissions));
            $this->command->newLine();
            $this->command->warn('⚠️  Beberapa permission tidak ditemukan. Silakan buat permission tersebut terlebih dahulu.');
            
            // Suggest creating missing permissions
            $this->command->info('Atau jalankan seeder berikut untuk membuat permission:');
            $this->command->line('  php artisan db:seed --class=MarketingPermissionSeeder');
        } else {
            $this->command->info('✅ Semua permission berhasil ditambahkan!');
        }
    }
}
