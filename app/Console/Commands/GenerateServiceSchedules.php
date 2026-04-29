<?php

namespace App\Console\Commands;

use App\Services\Operational\ServiceSchedulingService;
use Illuminate\Console\Command;

class GenerateServiceSchedules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:generate-schedules 
                            {--contract= : Generate schedules for specific contract ID}
                            {--overdue : Generate overdue service schedules}
                            {--all : Generate schedules for all active contracts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-generate service schedules based on contract frequency (Berdasarkan BRD)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serviceSchedulingService = new ServiceSchedulingService();

        if ($this->option('contract')) {
            $this->generateForSpecificContract($serviceSchedulingService);
        } elseif ($this->option('overdue')) {
            $this->generateOverdueSchedules($serviceSchedulingService);
        } elseif ($this->option('all')) {
            $this->generateForAllContracts($serviceSchedulingService);
        } else {
            $this->info('Please specify an option: --contract=ID, --overdue, or --all');
            return 1;
        }

        return 0;
    }

    /**
     * Generate service schedules for specific contract
     */
    private function generateForSpecificContract(ServiceSchedulingService $service)
    {
        $contractId = $this->option('contract');
        
        $this->info("Generating service schedules for contract ID: {$contractId}");
        
        $result = $service->generateServiceSchedulesForContract($contractId);
        
        if ($result['success']) {
            $this->info("✅ Successfully generated {$result['count']} service schedules");
            $this->table(
                ['Contract ID', 'Schedules Generated'],
                [[$contractId, $result['count']]]
            );
        } else {
            $this->error("❌ Failed to generate service schedules: {$result['message']}");
        }
    }

    /**
     * Generate overdue service schedules
     */
    private function generateOverdueSchedules(ServiceSchedulingService $service)
    {
        $this->info("Generating overdue service schedules...");
        
        $result = $service->generateOverdueServiceSchedules();
        
        if ($result['success']) {
            $this->info("✅ Successfully generated {$result['total_generated']} overdue service schedules");
            $this->info("📊 Processed {$result['contracts_processed']} contracts");
            
            if (!empty($result['results'])) {
                $this->table(
                    ['Contract Number', 'Catch-up Schedules'],
                    collect($result['results'])->map(function ($item) {
                        return [$item['contract_number'], $item['catch_up_schedules']];
                    })->toArray()
                );
            }
        } else {
            $this->error("❌ Failed to generate overdue service schedules: {$result['message']}");
        }
    }

    /**
     * Generate service schedules for all active contracts
     */
    private function generateForAllContracts(ServiceSchedulingService $service)
    {
        $this->info("Generating service schedules for all active contracts...");
        
        $result = $service->generateServiceSchedulesForAllActiveContracts();
        
        if ($result['success']) {
            $this->info("✅ Successfully generated {$result['total_schedules']} service schedules");
            $this->info("📊 Processed {$result['contracts_processed']} contracts");
            
            if (!empty($result['results'])) {
                $this->table(
                    ['Contract Number', 'Schedules Generated'],
                    collect($result['results'])->map(function ($item) {
                        return [$item['contract_number'], $item['schedules_generated']];
                    })->toArray()
                );
            }
        } else {
            $this->error("❌ Failed to generate service schedules: {$result['message']}");
        }
    }
}
