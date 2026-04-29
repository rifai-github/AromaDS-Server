<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Observers\UserObserver;

class TestObserver extends Command
{
    protected $signature = 'test:observer';
    protected $description = 'Test observer registration';

    public function handle()
    {
        $this->info('=== TESTING OBSERVER REGISTRATION ===');
        
        // Check if observer is registered
        $this->line('Testing observer registration...');
        
        // Test if observer methods exist
        $observer = new UserObserver();
        if ($observer) {
            $this->line('✓ UserObserver can be instantiated');
        } else {
            $this->error('✗ UserObserver cannot be instantiated');
        }
        
        // Test observer methods
        $this->newLine();
        $this->info('Testing observer methods:');
        
        $observer = new UserObserver();
        $methods = ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted'];
        
        foreach ($methods as $method) {
            if (method_exists($observer, $method)) {
                $this->line("✓ {$method} method exists");
            } else {
                $this->error("✗ {$method} method missing");
            }
        }
        
        // Test manual observer call
        $this->newLine();
        $this->info('Testing manual observer call:');
        
        $user = new User([
            'nik' => 'OBSERVER' . time(),
            'name' => 'Observer Test User',
            'email' => 'observer' . time() . '@example.com',
            'username' => 'observeruser' . time(),
            'password' => bcrypt('password'),
            'roles' => 'staff',
            'is_active' => true
        ]);
        
        try {
            $observer->creating($user);
            $this->line('✓ creating method called successfully');
        } catch (\Exception $e) {
            $this->error('✗ creating method failed: ' . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
}
