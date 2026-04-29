<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobSchedule;

class UpdateJobScheduleExpectedDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job-schedules:update-expected-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update expected dates for job schedules based on service frequency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update job schedule expected dates...');
        
        try {
            $updatedCount = JobSchedule::updateAllExpectedDates();
            
            $this->info("Successfully updated {$updatedCount} job schedules.");
            
            if ($updatedCount > 0) {
                $this->info('Updated job schedules:');
                $this->line('- Expected dates have been recalculated based on service frequency');
                $this->line('- Next service dates are now accurate');
            } else {
                $this->info('No job schedules needed updating.');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error updating job schedule expected dates: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
