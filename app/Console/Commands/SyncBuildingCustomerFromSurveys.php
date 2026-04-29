<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Survey;
use App\Models\Building;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class SyncBuildingCustomerFromSurveys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'survey:sync-building-customer {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync building-customer relationships from existing surveys';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $this->info('🔄 Syncing building-customer relationships from surveys...');
        $this->newLine();

        // Get all surveys with customer_id and building_id
        $surveys = Survey::whereNotNull('customer_id')
            ->whereNotNull('building_id')
            ->with(['customer', 'building'])
            ->get();

        $this->info("Found {$surveys->count()} surveys with customer and building");
        $this->newLine();

        $stats = [
            'total' => 0,
            'assigned' => 0,
            'already_assigned' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        $bar = $this->output->createProgressBar($surveys->count());
        $bar->start();

        foreach ($surveys as $survey) {
            $stats['total']++;
            
            try {
                $customerId = $survey->customer_id;
                $buildingId = $survey->building_id;

                // Skip if customer or building doesn't exist
                if (!$survey->customer || !$survey->building) {
                    $stats['skipped']++;
                    $bar->advance();
                    continue;
                }

                $building = $survey->building;
                $customer = $survey->customer;

                // Check if building-customer relationship already exists
                $exists = $building->customers()
                    ->where('customers.id', $customerId)
                    ->exists();

                if ($exists) {
                    $stats['already_assigned']++;
                } else {
                    // Assign building to customer
                    if (!$dryRun) {
                        $building->customers()->attach($customerId, [
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    $stats['assigned']++;
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->newLine();
                $this->error("Error processing survey ID {$survey->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display summary
        $this->info('📊 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Surveys Processed', $stats['total']],
                ['✅ New Assignments', $stats['assigned']],
                ['ℹ️ Already Assigned', $stats['already_assigned']],
                ['⏭️ Skipped (Missing Data)', $stats['skipped']],
                ['❌ Errors', $stats['errors']],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN - No changes were made');
            $this->info('Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('✅ Sync completed successfully!');
        }

        return 0;
    }
}
