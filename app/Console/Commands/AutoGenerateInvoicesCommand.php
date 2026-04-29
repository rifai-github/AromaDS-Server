<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Services\Finance\InvoiceGenerationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoGenerateInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:auto-generate-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-generate invoices for rental periods where all jobs are completed';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(InvoiceGenerationService $invoiceService)
    {
        $this->info('Starting auto-generation of invoices...');
        Log::info('Cron Job: Starting auto-generation of invoices.');

        // Get all active contracts that have not ended yet
        $contracts = Contract::where('status', 'active') // Assuming 'active' is the status for ongoing contracts
            ->where('end_date', '>=', now())
            ->get();

        $generatedCount = 0;
        $errorCount = 0;

        foreach ($contracts as $contract) {
            $this->info("Processing Contract: {$contract->contract_number}");

            try {
                // Get rental periods for this contract
                // We optimize by checking periods ending in the past (up to today)
                // because future periods definitely aren't "completed" yet.
                $periods = $invoiceService->getRentalPeriodsForContract($contract->id);
                
                foreach ($periods as $period) {
                    $periodEnd = Carbon::parse($period['period_end']);
                    
                    // Only process periods that have ended or are ending today
                    if ($periodEnd->isFuture()) {
                        continue;
                    }

                    // Check if period status is 'completed' (meaning all jobs done)
                    if ($period['status'] === 'completed') {
                        // Attempt generation
                        // The service already checks if invoice exists, so we can safely call it.
                        // However, to reduce overhead, we might want to check existence here too,
                        // but trusting the service's idempotency is safer logic-wise.
                        
                        $this->line(" - Checking period: {$period['rental_period']} ({$period['period_start']} - {$period['period_end']})");

                        $result = $invoiceService->autoGenerateInvoiceForRentalPeriod(
                            $contract->id,
                            $period['rental_period'],
                            Carbon::parse($period['period_start']),
                            Carbon::parse($period['period_end'])
                        );

                        if ($result['success']) {
                            $this->info("   [SUCCESS] Invoice generated: " . $result['invoice']->invoice_number);
                            Log::info("Cron Job: Generated invoice {$result['invoice']->invoice_number} for Contract {$contract->contract_number}");
                            $generatedCount++;
                        } elseif (str_contains($result['message'], 'Invoice already exists')) {
                            // This is expected for most periods
                            $this->line("   [SKIP] Invoice already exists.");
                        } else {
                            $this->warn("   [FAILED] " . $result['message']);
                            $errorCount++;
                        }
                    }
                }

            } catch (\Exception $e) {
                $this->error("Error processing contract {$contract->contract_number}: " . $e->getMessage());
                Log::error("Cron Job Error: Contract {$contract->contract_number} - " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("Invoice generation completed.");
        $this->info("Generated: {$generatedCount}, Errors: {$errorCount}");
        Log::info("Cron Job: Invoice generation completed. Generated: {$generatedCount}, Errors: {$errorCount}");

        return 0;
    }
}
