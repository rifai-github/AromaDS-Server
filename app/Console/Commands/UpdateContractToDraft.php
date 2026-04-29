<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;

class UpdateContractToDraft extends Command
{
    protected $signature = 'contract:set-draft {contract_number}';
    protected $description = 'Update contract status to draft';

    public function handle()
    {
        $contractNumber = $this->argument('contract_number');
        
        $contract = Contract::where('contract_number', $contractNumber)->first();
        
        if (!$contract) {
            $this->error("❌ Contract {$contractNumber} not found!");
            return 1;
        }
        
        $this->info("Before update:");
        $this->line("contract_status: {$contract->contract_status}");
        $this->line("status: " . $contract->getAttributes()['status']);
        
        // Update to draft
        $contract->update([
            'contract_status' => 'draft',
            'status' => 'draft' // sync both fields
        ]);
        
        $contract->refresh();
        
        $this->info("\nAfter update:");
        $this->line("contract_status: {$contract->contract_status}");
        $this->line("status: " . $contract->getAttributes()['status']);
        $this->info("\n✅ Contract {$contractNumber} updated to DRAFT - now can be finalized!");
        
        return 0;
    }
}

