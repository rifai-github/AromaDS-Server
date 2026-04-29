<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\AuditLog;

class SimpleAuditTest extends Command
{
    protected $signature = 'test:simple-audit';
    protected $description = 'Simple audit trail test';

    public function handle()
    {
        $this->info('=== SIMPLE AUDIT TRAIL TEST ===');
        
        // Test 1: Create user without auth
        $this->newLine();
        $this->info('1. Creating user without authentication:');
        
        $initialCount = AuditLog::count();
        
        $user = User::create([
            'nik' => 'SIMPLE' . time(),
            'name' => 'Simple Test User',
            'email' => 'simple' . time() . '@example.com',
            'username' => 'simpleuser' . time(),
            'password' => bcrypt('password'),
            'roles' => 'staff',
            'is_active' => true
        ]);
        
        $newCount = AuditLog::count();
        $this->line("Audit logs: {$initialCount} -> {$newCount}");
        
        if ($newCount > $initialCount) {
            $this->line('✓ Audit trail working');
        } else {
            $this->line('✗ Audit trail not working');
        }
        
        // Test 2: Check audit fields
        $this->newLine();
        $this->info('2. Checking audit fields:');
        $this->line("created_by: " . ($user->created_by ?? 'null'));
        $this->line("updated_by: " . ($user->updated_by ?? 'null'));
        $this->line("update_by_1: " . ($user->update_by_1 ?? 'null'));
        $this->line("update_at_1: " . ($user->update_at_1 ?? 'null'));
        
        // Test 3: Update user
        $this->newLine();
        $this->info('3. Updating user:');
        
        $updateCount = AuditLog::count();
        
        $user->update([
            'name' => 'Updated Simple Test User'
        ]);
        
        $updatedCount = AuditLog::count();
        $this->line("Audit logs: {$updateCount} -> {$updatedCount}");
        
        if ($updatedCount > $updateCount) {
            $this->line('✓ Update audit trail working');
        } else {
            $this->line('✗ Update audit trail not working');
        }
        
        // Cleanup
        $user->delete();
        
        $this->newLine();
        $this->info('=== SIMPLE AUDIT TEST COMPLETED ===');
        
        return Command::SUCCESS;
    }
}
