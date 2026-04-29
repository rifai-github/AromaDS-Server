<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContractRenewal;
use App\Models\ContractTermination;

class AutoCreateContractRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:auto-renew {--dry-run : Preview without creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-create renewal quotations for contracts nearing expiry (dynamic window based on contract duration)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting auto-renewal process...');
        $this->info('Checking contracts with dynamic renewal windows:');
        $this->info('  - Contract 12+ months → 90-120 days before expiry');
        $this->info('  - Contract 6-11 months → 60-90 days before expiry');
        $this->info('  - Contract 4-5 months → 60 days before expiry');
        $this->info('  - Contract 3 months → 30 days before expiry');
        $this->info('  - Contract < 3 months → 30 days minimum');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No data will be created');
            $this->newLine();
            
            // Preview mode
            $this->previewRenewals();
        } else {
            // Actual creation
            $renewals = ContractRenewal::autoCreateForExpiringContracts();
            
            if (count($renewals) > 0) {
                $this->info('✅ Auto-created ' . count($renewals) . ' renewal quotations:');
                foreach ($renewals as $renewal) {
                    $this->line("  - {$renewal->renewal_number} for Contract: {$renewal->contract->contract_number} (expires in {$renewal->days_before_expiry} days)");
                }
            } else {
                $this->comment('ℹ️  No contracts eligible for renewal at this time');
            }
        }

        $this->newLine();
        $this->info('✅ Auto-renewal process completed!');
        
        return Command::SUCCESS;
    }

    /**
     * Preview renewals without creating them
     */
    private function previewRenewals()
    {
        $activeContracts = \App\Models\Contract::where('status', 'active')
            ->whereDate('end_date', '>=', now())
            ->whereDoesntHave('renewals', function ($query) {
                $query->whereIn('status', [
                    ContractRenewal::STATUS_DRAFT,
                    ContractRenewal::STATUS_PENDING_CUSTOMER,
                    ContractRenewal::STATUS_CUSTOMER_APPROVED,
                    ContractRenewal::STATUS_PENDING_INTERNAL,
                    ContractRenewal::STATUS_APPROVED
                ]);
            })
            ->get();

        if ($activeContracts->count() === 0) {
            $this->comment('ℹ️  No active contracts found without existing renewal quotations');
            return;
        }

        $this->info("Found {$activeContracts->count()} active contracts:");
        $this->newLine();

        $eligibleCount = 0;
        foreach ($activeContracts as $contract) {
            $renewalWindowDays = ContractRenewal::calculateRenewalWindowDays($contract->start_date, $contract->end_date);
            $daysUntilExpiry = now()->diffInDays($contract->end_date, false);
            
            $status = $daysUntilExpiry <= $renewalWindowDays ? '✅ ELIGIBLE' : '⏳ NOT YET';
            $this->line("  {$status} - Contract: {$contract->contract_number}");
            $this->line("           Customer: {$contract->customer->name}");
            $this->line("           End Date: {$contract->end_date->format('Y-m-d')}");
            $this->line("           Days until expiry: {$daysUntilExpiry}");
            $this->line("           Renewal window: {$renewalWindowDays} days");
            
            if ($daysUntilExpiry <= $renewalWindowDays) {
                $this->line("           ➡️  WOULD CREATE: RNW-" . date('Ymd') . "-XXXX");
                $eligibleCount++;
            } else {
                $this->line("           ⏰ Available in: " . ($daysUntilExpiry - $renewalWindowDays) . " days");
            }
            
            $this->newLine();
        }

        $this->info("📊 Summary:");
        $this->line("  Total contracts checked: {$activeContracts->count()}");
        $this->line("  Eligible for renewal: {$eligibleCount}");
        $this->line("  Not yet eligible: " . ($activeContracts->count() - $eligibleCount));
    }
}

