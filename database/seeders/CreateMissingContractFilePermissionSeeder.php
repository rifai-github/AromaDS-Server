<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class CreateMissingContractFilePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the missing permission
        $permission = Permission::firstOrCreate(
            ['name' => 'marketing.contract_files.create'],
            [
                'description' => 'Create contract files',
                'system_reserved' => true,
                'is_active' => true
            ]
        );
        
        $this->command->info("✅ Permission '{$permission->name}' created (ID: {$permission->id})");
    }
}
