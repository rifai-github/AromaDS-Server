<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use App\Services\Operational\CancelledRemoveFreeSerialRestoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairCancelledRemoveFreeSerialNumbers extends Command
{
    protected $signature = 'jobs:repair-cancelled-remove-free-sn
                            {--job-number=* : Specific RF job number(s) to repair}
                            {--serial-number=* : Specific physical SN(s) confirmed still installed}
                            {--reactivate-uow : Reactivate removed Unit On Wall for confirmed serial-number repairs}
                            {--apply : Apply the repair (default is dry-run)}
                            {--limit=200 : Limit cancelled RF jobs when scanning automatically}';

    protected $description = 'Restore SN status to in_use/customer for cancelled Remove Free jobs';

    public function handle(CancelledRemoveFreeSerialRestoreService $restoreService): int
    {
        $jobNumbers = collect((array) $this->option('job-number'))
            ->filter()
            ->unique()
            ->values();
        $apply = (bool) $this->option('apply');
        $limit = max((int) $this->option('limit'), 1);
        $serialNumbers = collect((array) $this->option('serial-number'))
            ->filter()
            ->unique()
            ->values();
        $reactivateUow = (bool) $this->option('reactivate-uow');

        if (!$apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
            $this->newLine();
        }

        if ($serialNumbers->isNotEmpty()) {
            $this->warn('CONFIRMED SN mode. Only use this after tester/admin confirms the physical SN is still installed.');
            if (!$reactivateUow) {
                $this->line('Tip: add --reactivate-uow if the confirmed SN has Unit On Wall status removed.');
            }
            $this->newLine();
        }

        $jobs = $this->loadCancelledRemoveFreeJobs($jobNumbers, $limit);

        if ($jobs->isEmpty()) {
            $this->warn('No cancelled Remove Free jobs found.');
            return self::SUCCESS;
        }

        $totalCandidates = 0;
        $repairedJobs = 0;

        foreach ($jobs as $job) {
            $rows = DB::transaction(function () use ($restoreService, $job, $apply, $serialNumbers, $reactivateUow) {
                if ($serialNumbers->isNotEmpty()) {
                    return $restoreService->restoreConfirmedSerials(
                        $job,
                        $serialNumbers->all(),
                        $reactivateUow,
                        $apply
                    );
                }

                return $restoreService->restore($job, $apply);
            });

            if ($rows->isEmpty()) {
                $this->line($serialNumbers->isNotEmpty()
                    ? "[SKIP] {$job->job_number} | no matching confirmed SN found for this RF context"
                    : "[SKIP] {$job->job_number} | no active Unit On Wall with SN on_hand_remove"
                );
                continue;
            }

            $repairedJobs++;
            $totalCandidates += $rows->count();

            foreach ($rows as $row) {
                $statusLabel = $apply && ($row['can_apply'] ?? true) ? 'FIX ' : 'PLAN';
                if (($row['can_apply'] ?? true) === false) {
                    $statusLabel = 'HOLD';
                }

                $line = sprintf(
                    '[%s] %s | SN %s | UOW #%s | room %s | rental %s | %s -> %s/%s #%s',
                    $statusLabel,
                    $row['job_number'] ?: "Job #{$row['job_schedule_id']}",
                    $row['serial_number'],
                    $row['unit_on_wall_id'],
                    $row['room_id'] ?? '-',
                    $row['rental_id'] ?? '-',
                    $row['from_status'],
                    $row['to_status'],
                    $row['location_type'],
                    $row['location_id'] ?? '-'
                );

                if (array_key_exists('from_unit_status', $row)) {
                    $line .= sprintf(' | UOW %s -> %s', $row['from_unit_status'], $row['to_unit_status']);
                }

                if (!empty($row['conflict_unit_ids'])) {
                    $line .= ' | conflict active UOW: #' . implode(', #', $row['conflict_unit_ids']);
                }

                if (($row['reactivate_unit'] ?? false) && !$reactivateUow) {
                    $line .= ' | needs --reactivate-uow';
                }

                $this->line($line);
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d SN candidate(s) across %d job(s).',
            $apply ? 'Repaired' : 'Found',
            $totalCandidates,
            $repairedJobs
        ));

        return self::SUCCESS;
    }

    private function loadCancelledRemoveFreeJobs($jobNumbers, int $limit)
    {
        $query = JobSchedule::with([
                'jobAdvice',
                'jobScheduleRooms.rentals.jobAdviceRoom',
                'jobScheduleRooms.jobAdviceRoom',
            ])
            ->where('status', 'cancelled')
            ->whereIn(DB::raw('LOWER(TRIM(type))'), ['remove_free', 'remove free'])
            ->orderByDesc('id');

        if ($jobNumbers->isNotEmpty()) {
            $query->whereIn('job_number', $jobNumbers->all());
        } else {
            $query->limit($limit);
        }

        return $query->get();
    }
}
