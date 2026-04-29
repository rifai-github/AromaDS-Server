<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;

class CheckContractStatus extends Command
{
    protected $signature = 'contract:check-status {contract_number}';
    protected $description = 'Check contract status from database';

    public function handle()
    {
        $contractNumber = $this->argument('contract_number');
        
        $contract = Contract::where('contract_number', $contractNumber)->first();
        
        if (!$contract) {
            $this->error("❌ Contract {$contractNumber} not found!");
            return 1;
        }
        
        $this->info("=== CONTRACT DATA ===");
        $this->line("ID: {$contract->id}");
        $this->line("Number: {$contract->contract_number}");
        $this->line("Customer: " . ($contract->customer->name ?? 'N/A'));
        
        $this->info("\n--- STATUS FIELD ---");
        $this->line("contract_status: {$contract->contract_status}");
        
        $this->info("\n--- RAW DATABASE ATTRIBUTES (status fields) ---");
        $attrs = $contract->getAttributes();
        foreach ($attrs as $key => $value) {
            if (strpos($key, 'status') !== false) {
                $this->line("$key: $value");
            }
        }
        
        return 0;
    }
}

