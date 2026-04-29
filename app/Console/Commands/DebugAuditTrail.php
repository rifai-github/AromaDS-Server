<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\AuditLog;

class DebugAuditTrail extends Command
{
    protected $signature = 'debug:audit-trail';
    protected $description = 'Debug audit trail functionality';

    public function handle()
    {
        $this->info('=== DEBUGGING AUDIT TRAIL ===');
        
        // Check if trait is loaded
        $user = new User();
        $traits = class_uses_recursive($user);
        $this->line('User model traits: ' . implode(', ', $traits));
        
        // Check if boot method exists
        if (method_exists($user, 'bootHasComprehensiveAuditTrail')) {
            $this->line('✓ bootHasComprehensiveAuditTrail method exists');
        } else {
            $this->error('✗ bootHasComprehensiveAuditTrail method missing');
        }
        
        // Check if setAuditFields method exists
        if (method_exists($user, 'setAuditFields')) {
            $this->line('✓ setAuditFields method exists');
        } else {
            $this->error('✗ setAuditFields method missing');
        }
        
        // Test manual audit log creation
        $this->newLine();
        $this->info('Testing manual audit log creation:');
        $initialCount = AuditLog::count();
        
        // Get a real user ID
        $realUser = User::first();
        if (!$realUser) {
            $this->error('✗ No users found in database');
            return Command::SUCCESS;
        }
        
        try {
            AuditLog::create([
                'model_type' => 'App\Models\User',
                'model_id' => $realUser->id,
                'action' => 'test',
                'old_values' => null,
                'new_values' => ['test' => 'data'],
                'changed_fields' => ['test'],
                'user_id' => $realUser->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test'
            ]);
            
            $newCount = AuditLog::count();
            if ($newCount > $initialCount) {
                $this->line('✓ Manual audit log creation works');
            } else {
                $this->error('✗ Manual audit log creation failed');
            }
        } catch (\Exception $e) {
            $this->error('✗ Manual audit log creation error: ' . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
}
