<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractRenewal;
use Illuminate\Console\Command;

class AutoCreateContractRenewals extends Command
{
    protected $signature = 'contracts:auto-renew {--dry-run : Preview without creating}';

    protected $description = 'Auto-create renewal quotations for contracts nearing expiry (dynamic window based on contract duration)';

    public function handle()
    {
        $this->info('Starting auto-renewal process...');
        $this->info('Checking contracts with dynamic renewal windows:');
        $this->info('  - Contract 12+ months -> 90-120 days before expiry');
        $this->info('  - Contract 6-11 months -> 60-90 days before expiry');
        $this->info('  - Contract 4-5 months -> 60 days before expiry');
        $this->info('  - Contract 3 months -> 30 days before expiry');
        $this->info('  - Contract < 3 months -> 30 days minimum');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No data will be created');
            $this->newLine();
            $this->previewRenewals();
        } else {
            $renewals = ContractRenewal::autoCreateForExpiringContracts();

            if (count($renewals) > 0) {
                $this->info('Auto-created ' . count($renewals) . ' renewal quotations:');
                foreach ($renewals as $renewal) {
                    $this->line("  - {$renewal->renewal_number} for Contract: {$renewal->contract->contract_number} (expires in {$renewal->days_before_expiry} days)");
                }
            } else {
                $this->comment('No contracts eligible for renewal at this time');
            }
        }

        $this->newLine();
        $this->info('Auto-renewal process completed!');

        return Command::SUCCESS;
    }

    private function previewRenewals(): void
    {
        $activeContracts = Contract::where('contract_status', 'active')
            ->whereDate('end_date', '>=', now())
            ->whereDoesntHave('renewals', function ($query) {
                $query->whereIn('status', [
                    ContractRenewal::STATUS_DRAFT,
                    ContractRenewal::STATUS_PENDING_CUSTOMER,
                    ContractRenewal::STATUS_CUSTOMER_APPROVED,
                    ContractRenewal::STATUS_PENDING_INTERNAL,
                    ContractRenewal::STATUS_APPROVED,
                ]);
            })
            ->get();

        if ($activeContracts->count() === 0) {
            $this->comment('No active contracts found without existing renewal quotations');
            return;
        }

        $this->info("Found {$activeContracts->count()} active contracts:");
        $this->newLine();

        $eligibleCount = 0;
        foreach ($activeContracts as $contract) {
            $eligibility = ContractRenewal::isEligibleForRenewal($contract->id);
            $renewalWindowDays = $eligibility['renewal_window_days'] ?? null;
            $daysUntilExpiry = $eligibility['days_until_expiry'] ?? null;

            $status = $eligibility['eligible'] ? 'ELIGIBLE' : 'BLOCKED';
            $this->line("  {$status} - Contract: {$contract->contract_number}");
            $this->line("           Customer: " . ($contract->customer->name ?? '-'));
            $this->line("           End Date: " . ($contract->actual_end_date?->format('Y-m-d') ?? $contract->end_date?->format('Y-m-d') ?? '-'));
            $this->line("           Days until expiry: " . ($daysUntilExpiry ?? '-'));
            $this->line("           Renewal window: " . ($renewalWindowDays ? "{$renewalWindowDays} days" : '-'));

            if ($eligibility['eligible']) {
                $this->line("           WOULD CREATE: RNW-" . date('Ymd') . "-XXXX");
                $eligibleCount++;
            } else {
                $this->line("           Reason: " . ($eligibility['reason'] ?? 'Not eligible'));
            }

            $this->newLine();
        }

        $this->info('Summary:');
        $this->line("  Total contracts checked: {$activeContracts->count()}");
        $this->line("  Eligible for renewal: {$eligibleCount}");
        $this->line("  Not eligible: " . ($activeContracts->count() - $eligibleCount));
    }
}
