<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use Carbon\Carbon;

class SetContractForRenewalTest extends Command
{
    protected $signature = 'contract:set-for-renewal-test {contract_id}';
    protected $description = 'Set a contract end date to be eligible for renewal testing';

    public function handle()
    {
        $contractId = $this->argument('contract_id');
        
        $contract = Contract::find($contractId);
        
        if (!$contract) {
            $this->error("❌ Contract ID {$contractId} not found!");
            return 1;
        }
        
        $this->info("=== BEFORE ===");
        $this->line("Contract: {$contract->contract_number}");
        $this->line("Start Date: {$contract->start_date}");
        $this->line("End Date: {$contract->end_date}");
        $this->line("Status: {$contract->contract_status}");
        
        // Calculate contract duration (should keep the same duration)
        $startDate = Carbon::parse($contract->start_date);
        $endDate = Carbon::parse($contract->end_date);
        $durationDays = $startDate->diffInDays($endDate);
        
        $this->line("\nContract Duration: {$durationDays} days");
        $renewalWindowDays = min(120, max(30, intval($durationDays / 3)));
        $this->line("Renewal Window: {$renewalWindowDays} days before expiry");
        
        // Set end date to 60 days from now (well within renewal window)
        $newEndDate = now()->addDays(60);
        $newStartDate = (clone $newEndDate)->subDays($durationDays);
        
        $contract->update([
            'start_date' => $newStartDate,
            'end_date' => $newEndDate
        ]);
        
        $contract->refresh();
        
        $this->info("\n=== AFTER ===");
        $this->line("Contract: {$contract->contract_number}");
        $this->line("Start Date: {$contract->start_date}");
        $this->line("End Date: {$contract->end_date}");
        $this->line("Status: {$contract->contract_status}");
        
        $daysUntilExpiry = now()->diffInDays($contract->end_date, false);
        $this->info("\n✅ Days until expiry: {$daysUntilExpiry} days");
        $this->info("✅ Renewal window: {$renewalWindowDays} days");
        
        if ($daysUntilExpiry <= $renewalWindowDays) {
            $this->info("\n🎉 Contract is NOW ELIGIBLE for renewal testing!");
        } else {
            $this->warn("\n⚠️  Contract is still not eligible (too far from expiry)");
        }
        
        return 0;
    }
}

