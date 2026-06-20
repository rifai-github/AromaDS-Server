<?php

namespace App\Console\Commands;

use App\Http\Controllers\Operational\JobScheduleController;
use App\Models\JobAdvice;
use App\Models\JobSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off repair for the multi-month service frequency bug fixed in
 * JobScheduleController::generateAllRemainingServices /
 * calculateTotalServicePeriodsForRental (commit 52180fc). Quarterly/4-monthly
 * rentals had generated one service job per month instead of one per
 * frequency_months interval.
 *
 * For each affected Job Advice this deletes the wrongly-spaced, not-yet-worked
 * periods (status scheduled/new_job with no job_number) and re-runs the fixed
 * generator, which skips any period that still exists (e.g. an already
 * in-progress period whose date happens to already fall on the correct
 * interval).
 */
class RepairWrongFrequencyServicePeriods extends Command
{
    protected $signature = 'job-schedules:repair-frequency-periods
        {--job-advice=* : Job Advice ID(s) to repair. Required.}
        {--apply : Apply the repair. Default is dry-run}';

    protected $description = 'Delete wrongly-spaced (monthly) service periods for multi-month rentals and regenerate them at the correct interval';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $jobAdviceIds = array_map('intval', $this->option('job-advice'));

        if (empty($jobAdviceIds)) {
            $this->error('Pass at least one --job-advice=<id>.');

            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist the repair.');
        }

        foreach ($jobAdviceIds as $jobAdviceId) {
            $this->repairOne($jobAdviceId, $apply);
        }

        return self::SUCCESS;
    }

    private function repairOne(int $jobAdviceId, bool $apply): void
    {
        $jobAdvice = JobAdvice::with('contract')->find($jobAdviceId);

        if (! $jobAdvice) {
            $this->error("Job Advice #{$jobAdviceId} not found. Skipping.");

            return;
        }

        $this->info("=== Job Advice #{$jobAdviceId} ({$jobAdvice->job_advice_number}) ===");

        $services = JobSchedule::where('job_advice_id', $jobAdviceId)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->orderBy('period')
            ->get();

        $firstService = $services->firstWhere('period', 1) ?? $services->first();

        if (! $firstService) {
            $this->warn('No service jobs found. Skipping.');

            return;
        }

        $toDelete = $services->filter(function ($job) {
            return (int) $job->period !== 1
                && empty($job->job_number)
                && in_array($job->status, ['scheduled', 'new_job'], true);
        });

        $toKeep = $services->diff($toDelete);

        $this->line('Keeping (already worked / has job number):');
        foreach ($toKeep as $job) {
            $this->line("  id={$job->id} period={$job->period} date={$job->schedule_date->toDateString()} status={$job->status} job_number=" . ($job->job_number ?: '-'));
        }

        $this->line('Deleting (wrong spacing, not yet worked):');
        foreach ($toDelete as $job) {
            $this->line("  id={$job->id} period={$job->period} date={$job->schedule_date->toDateString()} status={$job->status}");
        }

        if (! $apply) {
            return;
        }

        DB::transaction(function () use ($toDelete, $firstService, $jobAdvice) {
            foreach ($toDelete as $job) {
                $job->jobScheduleRooms()->delete();
                $job->delete();
            }

            $controller = new JobScheduleController();
            $method = new \ReflectionMethod($controller, 'generateAllRemainingServices');
            $method->setAccessible(true);
            $created = $method->invoke($controller, $firstService->fresh(), $jobAdvice);

            $this->info('Regenerated: ' . (is_array($created) ? count($created) : 0) . ' service job(s).');
        });

        $final = JobSchedule::where('job_advice_id', $jobAdvice->id)
            ->whereIn('type', ['service', 'service_first', 'service_routine'])
            ->orderBy('period')
            ->get(['id', 'period', 'schedule_date', 'status', 'job_number']);

        $this->line('Final state:');
        foreach ($final as $job) {
            $this->line("  id={$job->id} period={$job->period} date={$job->schedule_date->toDateString()} status={$job->status} job_number=" . ($job->job_number ?: '-'));
        }
    }
}
