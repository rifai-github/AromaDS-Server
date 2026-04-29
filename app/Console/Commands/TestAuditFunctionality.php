<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Branch;
use App\Models\MasterProduct;
use App\Models\AuditLog;

class TestAuditFunctionality extends Command
{
    protected $signature = 'test:audit-functionality';
    protected $description = 'Test audit trail functionality by creating/updating records';

    public function handle()
    {
        $this->info('=== TESTING AUDIT TRAIL FUNCTIONALITY ===');
        $this->newLine();

        // Test 1: Create a new user and check audit trail
        $this->info('1. Testing User Creation Audit Trail:');
        $initialAuditCount = AuditLog::count();
        
        $user = User::create([
            'nik' => 'TEST' . time(),
            'name' => 'Test User',
            'email' => 'test' . time() . '@example.com',
            'username' => 'testuser' . time(),
            'password' => bcrypt('password'),
            'roles' => 'staff',
            'is_active' => true
        ]);
        
        $newAuditCount = AuditLog::count();
        $auditCreated = $newAuditCount > $initialAuditCount;
        
        if ($auditCreated) {
            $this->line("   ✓ User creation logged in audit trail");
            $this->line("   ✓ Audit logs increased from {$initialAuditCount} to {$newAuditCount}");
        } else {
            $this->error("   ✗ User creation not logged in audit trail");
        }

        // Test 2: Update user and check audit trail
        $this->newLine();
        $this->info('2. Testing User Update Audit Trail:');
        $updateAuditCount = AuditLog::count();
        
        $user->update([
            'name' => 'Updated Test User',
            'email' => 'updated' . time() . '@example.com'
        ]);
        
        $updatedAuditCount = AuditLog::count();
        $auditUpdated = $updatedAuditCount > $updateAuditCount;
        
        if ($auditUpdated) {
            $this->line("   ✓ User update logged in audit trail");
            $this->line("   ✓ Audit logs increased from {$updateAuditCount} to {$updatedAuditCount}");
        } else {
            $this->error("   ✗ User update not logged in audit trail");
        }

        // Test 3: Check audit trail fields in user record
        $this->newLine();
        $this->info('3. Testing Audit Trail Fields in User Record:');
        $user->refresh();
        
        $auditFields = [
            'created_by' => $user->created_by,
            'updated_by' => $user->updated_by,
            'update_by_1' => $user->update_by_1,
            'update_at_1' => $user->update_at_1,
            'update_by_2' => $user->update_by_2,
            'update_at_2' => $user->update_at_2
        ];
        
        foreach ($auditFields as $field => $value) {
            if ($value !== null) {
                $this->line("   ✓ {$field}: {$value}");
            } else {
                $this->line("   ! {$field}: null (normal for new records)");
            }
        }

        // Test 4: Test Branch creation audit trail
        $this->newLine();
        $this->info('4. Testing Branch Creation Audit Trail:');
        $branchAuditCount = AuditLog::count();
        
        $branch = Branch::create([
            'code' => 'TEST' . time(),
            'name' => 'Test Branch',
            'address_type' => 'office',
            'address_1' => 'Test Address',
            'is_active' => true
        ]);
        
        $branchAuditCountNew = AuditLog::count();
        $branchAuditCreated = $branchAuditCountNew > $branchAuditCount;
        
        if ($branchAuditCreated) {
            $this->line("   ✓ Branch creation logged in audit trail");
        } else {
            $this->error("   ✗ Branch creation not logged in audit trail");
        }

        // Test 5: Test MasterProduct creation audit trail
        $this->newLine();
        $this->info('5. Testing MasterProduct Creation Audit Trail:');
        $productAuditCount = AuditLog::count();
        
        $product = MasterProduct::create([
            'sku' => 'TEST-PROD-' . time(),
            'product_type_id' => 1,
            'name' => 'Test Product',
            'description' => 'Test Product Description',
            'is_active' => true
        ]);
        
        $productAuditCountNew = AuditLog::count();
        $productAuditCreated = $productAuditCountNew > $productAuditCount;
        
        if ($productAuditCreated) {
            $this->line("   ✓ MasterProduct creation logged in audit trail");
        } else {
            $this->error("   ✗ MasterProduct creation not logged in audit trail");
        }

        // Cleanup test data
        $this->newLine();
        $this->info('6. Cleaning up test data:');
        $user->delete();
        $branch->delete();
        $product->delete();
        $this->line("   ✓ Test data cleaned up");

        $this->newLine();
        $this->info('=== AUDIT TRAIL FUNCTIONALITY TEST COMPLETED ===');
        
        return Command::SUCCESS;
    }
}
