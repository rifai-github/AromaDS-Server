<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RollbackJobAdvice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job-advice:rollback {id : Job Advice ID to rollback}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback Job Advice to waiting_for_approval status and delete associated Job Schedules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        
        $jobAdvice = JobAdvice::with('rooms')->find($id);
        
        if (!$jobAdvice) {
            $this->error("Job Advice with ID {$id} not found.");
            return 1;
        }
        
        $this->info("Rolling back Job Advice: {$jobAdvice->job_advice_number} (ID: {$id})");
        $this->info("Current status: {$jobAdvice->status}");
        
        if ($jobAdvice->status !== 'approved') {
            $this->warn("Job Advice is not in 'approved' status. Current status: {$jobAdvice->status}");
            if (!$this->confirm('Do you want to continue anyway?')) {
                $this->info('Rollback cancelled.');
                return 0;
            }
        }
        
        DB::beginTransaction();
        try {
            // Delete Job Schedules associated with this Job Advice
            $jobSchedules = JobSchedule::where('job_advice_id', $jobAdvice->id)->get();
            $jobScheduleCount = $jobSchedules->count();
            
            if ($jobScheduleCount > 0) {
                $this->info("Found {$jobScheduleCount} Job Schedule(s) to delete:");
                foreach ($jobSchedules as $js) {
                    $this->line("  - {$js->job_number} (ID: {$js->id}, Type: {$js->type}, Status: {$js->status})");
                }
                
                // Soft delete Job Schedules
                foreach ($jobSchedules as $js) {
                    $js->delete();
                    $this->info("  ✓ Deleted Job Schedule: {$js->job_number}");
                    Log::info("RollbackJobAdvice: Deleted Job Schedule {$js->job_number} (ID: {$js->id}) for Job Advice {$jobAdvice->job_advice_number}");
                }
            } else {
                $this->info("No Job Schedules found for this Job Advice.");
            }
            
            // Reset job schedule IDs in job_advice_rooms
            foreach ($jobAdvice->rooms as $jaRoom) {
                $jaRoom->update([
                    'install_job_schedule_id' => null,
                    'service_job_schedule_id' => null,
                    'remove_job_schedule_id' => null,
                    'status' => 'pending',
                    'updated_by' => \App\Models\User::first()?->id ?? null // Use first user or null
                ]);
                $this->info("  ✓ Reset job schedule IDs for room: {$jaRoom->room_name}");
            }
            
            // Rollback Job Advice status
            $jobAdvice->update([
                'status' => 'waiting_for_approval',
                'approved_by' => null,
                'date_approval' => null,
                'updated_by' => \App\Models\User::first()?->id ?? null // Use first user or null
            ]);
            
            $this->info("  ✓ Updated Job Advice status to 'waiting_for_approval'");
            $this->info("  ✓ Reset approved_by and date_approval");
            
            DB::commit();
            
            $this->info("\n✅ Job Advice {$jobAdvice->job_advice_number} successfully rolled back!");
            $this->info("   Status: waiting_for_approval");
            $this->info("   Deleted Job Schedules: {$jobScheduleCount}");
            $this->info("   Reset Rooms: {$jobAdvice->rooms->count()}");
            
            Log::info("RollbackJobAdvice: Successfully rolled back Job Advice {$jobAdvice->job_advice_number} (ID: {$id})");
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error rolling back Job Advice: " . $e->getMessage());
            Log::error("RollbackJobAdvice: Error rolling back Job Advice {$jobAdvice->job_advice_number}: " . $e->getMessage());
            return 1;
        }
    }
}

