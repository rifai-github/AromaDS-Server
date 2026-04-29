<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\AuditLog;

class TestModelEvents extends Command
{
    protected $signature = 'test:model-events';
    protected $description = 'Test model events and audit trail';

    public function handle()
    {
        $this->info('=== TESTING MODEL EVENTS ===');
        
        // Test 1: Check if events are registered
        $this->newLine();
        $this->info('1. Checking model events:');
        
        $user = new User();
        $events = $user->getObservableEvents();
        $this->line('Observable events: ' . implode(', ', $events));
        
        // Test 2: Test creating event manually
        $this->newLine();
        $this->info('2. Testing creating event manually:');
        
        $initialCount = AuditLog::count();
        
        // Create user with unique data
        $user = User::create([
            'nik' => 'EVENT' . time(),
            'name' => 'Event Test User',
            'email' => 'event' . time() . '@example.com',
            'username' => 'eventuser' . time(),
            'password' => bcrypt('password'),
            'roles' => 'staff',
            'is_active' => true
        ]);
        
        $newCount = AuditLog::count();
        $this->line("Audit logs before: {$initialCount}, after: {$newCount}");
        
        if ($newCount > $initialCount) {
            $this->line('✓ Creating event triggered audit log');
        } else {
            $this->line('✗ Creating event did not trigger audit log');
        }
        
        // Test 3: Test updating event manually
        $this->newLine();
        $this->info('3. Testing updating event manually:');
        
        $updateCount = AuditLog::count();
        
        $user->update([
            'name' => 'Updated Event Test User'
        ]);
        
        $updatedCount = AuditLog::count();
        $this->line("Audit logs before update: {$updateCount}, after: {$updatedCount}");
        
        if ($updatedCount > $updateCount) {
            $this->line('✓ Updating event triggered audit log');
        } else {
            $this->line('✗ Updating event did not trigger audit log');
        }
        
        // Cleanup
        $user->delete();
        
        $this->newLine();
        $this->info('=== MODEL EVENTS TEST COMPLETED ===');
        
        return Command::SUCCESS;
    }
}
