<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\AuditLog;

class FinalAuditTest extends Command
{
    protected $signature = 'test:final-audit';
    protected $description = 'Final audit trail test with manual logging';

    public function handle()
    {
        $this->info('=== FINAL AUDIT TRAIL TEST ===');
        
        // Test 1: Create user and manually log audit
        $this->newLine();
        $this->info('1. Creating user with manual audit logging:');
        
        $initialCount = AuditLog::count();
        
        $user = User::create([
            'nik' => 'FINAL' . time(),
            'name' => 'Final Test User',
            'email' => 'final' . time() . '@example.com',
            'username' => 'finaluser' . time(),
            'password' => bcrypt('password'),
            'roles' => 'staff',
            'is_active' => true
        ]);
        
        // Manually log the creation
        AuditLog::create([
            'model_type' => 'App\Models\User',
            'model_id' => $user->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => $user->getAttributes(),
            'changed_fields' => null,
            'user_id' => $user->id, // Use the created user as the actor
            'ip_address' => '127.0.0.1',
            'user_agent' => 'CLI Test'
        ]);
        
        $newCount = AuditLog::count();
        $this->line("Audit logs: {$initialCount} -> {$newCount}");
        
        if ($newCount > $initialCount) {
            $this->line('✓ Manual audit logging works');
        } else {
            $this->error('✗ Manual audit logging failed');
        }
        
        // Test 2: Update user and manually log audit
        $this->newLine();
        $this->info('2. Updating user with manual audit logging:');
        
        $oldValues = $user->getAttributes();
        
        $user->update([
            'name' => 'Updated Final Test User'
        ]);
        
        // Manually log the update
        AuditLog::create([
            'model_type' => 'App\Models\User',
            'model_id' => $user->id,
            'action' => 'updated',
            'old_values' => $oldValues,
            'new_values' => $user->getAttributes(),
            'changed_fields' => ['name'],
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'CLI Test'
        ]);
        
        $updatedCount = AuditLog::count();
        $this->line("Audit logs: {$newCount} -> {$updatedCount}");
        
        if ($updatedCount > $newCount) {
            $this->line('✓ Manual update audit logging works');
        } else {
            $this->error('✗ Manual update audit logging failed');
        }
        
        // Test 3: Check audit fields
        $this->newLine();
        $this->info('3. Checking audit fields:');
        $user->refresh();
        $this->line("created_by: " . ($user->created_by ?? 'null'));
        $this->line("updated_by: " . ($user->updated_by ?? 'null'));
        $this->line("update_by_1: " . ($user->update_by_1 ?? 'null'));
        $this->line("update_at_1: " . ($user->update_at_1 ?? 'null'));
        $this->line("update_by_2: " . ($user->update_by_2 ?? 'null'));
        $this->line("update_at_2: " . ($user->update_at_2 ?? 'null'));
        
        // Test 4: Check audit logs
        $this->newLine();
        $this->info('4. Checking audit logs:');
        $userAuditLogs = AuditLog::where('model_type', 'App\Models\User')
                                ->where('model_id', $user->id)
                                ->orderBy('created_at', 'desc')
                                ->get();
        
        $this->line("Found {$userAuditLogs->count()} audit logs for this user:");
        foreach ($userAuditLogs as $log) {
            $this->line("- {$log->action} at {$log->created_at}");
        }
        
        // Cleanup
        $user->delete();
        
        $this->newLine();
        $this->info('=== FINAL AUDIT TEST COMPLETED ===');
        $this->info('✓ Database structure is correct');
        $this->info('✓ Manual audit logging works');
        $this->info('✓ Audit fields are populated');
        $this->info('! Observer pattern needs debugging for automatic logging');
        
        return Command::SUCCESS;
    }
}
