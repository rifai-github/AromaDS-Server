<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckAuditColumns extends Command
{
    protected $signature = 'check:audit-columns';
    protected $description = 'Check if audit trail columns exist';

    public function handle()
    {
        $this->info('=== CHECKING AUDIT TRAIL COLUMNS ===');
        
        $tables = ['users', 'branches', 'master_products', 'master_rentals', 'contracts'];
        $auditFields = ['update_by_1', 'update_at_1', 'update_by_2', 'update_at_2', 'delete_by', 'delete_at'];
        
        foreach ($tables as $table) {
            $this->info("Checking table: {$table}");
            if (Schema::hasTable($table)) {
                $columns = Schema::getColumnListing($table);
                foreach ($auditFields as $field) {
                    $exists = in_array($field, $columns);
                    $status = $exists ? '✓ EXISTS' : '✗ MISSING';
                    $this->line("  {$field}: {$status}");
                }
            } else {
                $this->error("  Table {$table} does not exist");
            }
            $this->newLine();
        }
        
        // Check audit_logs table
        $this->info('Checking audit_logs table:');
        if (Schema::hasTable('audit_logs')) {
            $columns = Schema::getColumnListing('audit_logs');
            $hasChangedFields = in_array('changed_fields', $columns);
            $status = $hasChangedFields ? '✓ EXISTS' : '✗ MISSING';
            $this->line("  changed_fields: {$status}");
        }
        
        // Check login_histories table
        $this->info('Checking login_histories table:');
        if (Schema::hasTable('login_histories')) {
            $columns = Schema::getColumnListing('login_histories');
            $hasLocation = in_array('location', $columns);
            $status = $hasLocation ? '✓ EXISTS' : '✗ MISSING';
            $this->line("  location: {$status}");
        }
        
        return Command::SUCCESS;
    }
}
