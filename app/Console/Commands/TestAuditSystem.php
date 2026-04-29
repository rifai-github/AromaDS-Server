<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserAccessLevel;
use App\Models\UserLoginRestriction;
use App\Models\AuditLog;

class TestAuditSystem extends Command
{
    protected $signature = 'test:audit-system';
    protected $description = 'Test audit trail and access control system';

    public function handle()
    {
        $this->info('=== TESTING AUDIT TRAIL & ACCESS CONTROL SYSTEM ===');
        $this->newLine();

        // Test 1: Check if admin user exists and has access levels
        $this->info('1. Testing Admin User Access Levels:');
        $admin = User::where('username', 'admin')->first();
        if ($admin) {
            $this->line("   ✓ Admin user found (ID: {$admin->id})");
            $this->line("   ✓ Access levels count: " . $admin->accessLevels->count());
            $this->line("   ✓ Login restrictions count: " . $admin->loginRestrictions->count());
        } else {
            $this->error("   ✗ Admin user not found");
        }

        // Test 2: Check if audit trail fields exist in users table
        $this->newLine();
        $this->info('2. Testing Audit Trail Fields:');
        $user = User::first();
        if ($user) {
            $auditFields = ['update_by_1', 'update_at_1', 'update_by_2', 'update_at_2', 'delete_by', 'delete_at'];
            foreach ($auditFields as $field) {
                // Check if column exists in database
                $columnExists = \Schema::hasColumn('users', $field);
                if ($columnExists) {
                    $this->line("   ✓ Field '{$field}' exists in database");
                } else {
                    $this->error("   ✗ Field '{$field}' missing from database");
                }
            }
        }

        // Test 3: Check if access control tables exist and have data
        $this->newLine();
        $this->info('3. Testing Access Control Tables:');
        $accessLevels = UserAccessLevel::count();
        $loginRestrictions = UserLoginRestriction::count();
        $this->line("   ✓ User access levels count: {$accessLevels}");
        $this->line("   ✓ User login restrictions count: {$loginRestrictions}");

        // Test 4: Check if audit logs table has enhanced fields
        $this->newLine();
        $this->info('4. Testing Enhanced Audit Logs:');
        $hasChangedFields = \Schema::hasColumn('audit_logs', 'changed_fields');
        if ($hasChangedFields) {
            $this->line("   ✓ 'changed_fields' column exists in audit_logs");
        } else {
            $this->error("   ✗ 'changed_fields' column missing from audit_logs");
        }
        
        $auditLog = AuditLog::first();
        if ($auditLog) {
            $this->line("   ✓ Audit logs table has data");
        } else {
            $this->line("   ! No audit logs found (this is normal for new system)");
        }

        // Test 5: Test User Model Relationships
        $this->newLine();
        $this->info('5. Testing User Model Relationships:');
        if ($admin) {
            try {
                $accessLevels = $admin->accessLevels;
                $this->line("   ✓ accessLevels() relationship works");
            } catch (\Exception $e) {
                $this->error("   ✗ accessLevels() relationship failed: " . $e->getMessage());
            }
            
            try {
                $loginRestrictions = $admin->loginRestrictions;
                $this->line("   ✓ loginRestrictions() relationship works");
            } catch (\Exception $e) {
                $this->error("   ✗ loginRestrictions() relationship failed: " . $e->getMessage());
            }
            
            try {
                $loginHistories = $admin->loginHistories;
                $this->line("   ✓ loginHistories() relationship works");
            } catch (\Exception $e) {
                $this->error("   ✗ loginHistories() relationship failed: " . $e->getMessage());
            }
        }

        // Test 6: Test Audit Trail Trait
        $this->newLine();
        $this->info('6. Testing Audit Trail Trait:');
        if ($user) {
            $traits = class_uses_recursive($user);
            if (in_array('App\Traits\HasComprehensiveAuditTrail', $traits)) {
                $this->line("   ✓ HasComprehensiveAuditTrail trait is loaded");
            } else {
                $this->error("   ✗ HasComprehensiveAuditTrail trait not found");
            }
        }

        $this->newLine();
        $this->info('=== TESTING COMPLETED ===');
        
        return Command::SUCCESS;
    }
}
