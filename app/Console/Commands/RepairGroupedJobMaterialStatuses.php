<?php

namespace App\Console\Commands;

use App\Models\InventoryIssuing;
use App\Models\JobAssignMaterialIssue;
use App\Models\JobSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairGroupedJobMaterialStatuses extends Command
{
    protected $signature = 'jobs:repair-grouped-material-statuses
                            {--job-number=* : Specific job number(s) to repair}
                            {--apply : Apply the repair (default is dry-run)}
                            {--include-advanced : Also update jobs already in advanced statuses}
                            {--limit=200 : Limit groups when scanning automatically}';

    protected $description = 'Repair mismatched material statuses across grouped job schedules with the same job number';

    public function handle()
    {
        $jobNumbers = collect((array) $this->option('job-number'))
            ->filter()
            ->unique()
            ->values();
        $apply = (bool) $this->option('apply');
        $includeAdvanced = (bool) $this->option('include-advanced');
        $limit = max((int) $this->option('limit'), 1);

        if (!$apply) {
            $this->info('DRY RUN mode active. No database changes will be made.');
            $this->newLine();
        }

        $groups = $this->loadJobGroups($jobNumbers, $limit);

        if ($groups->isEmpty()) {
            $this->warn('No grouped job numbers found to inspect.');
            return self::SUCCESS;
        }

        $updatedGroups = 0;
        $skippedGroups = 0;
        $affectedRows = 0;

        foreach ($groups as $jobNumber => $jobs) {
            $analysis = $this->analyzeGroup($jobs, $includeAdvanced);

            if (!$analysis['repairable']) {
                $skippedGroups++;
                $this->line(sprintf(
                    '[SKIP] %s | %s',
                    $jobNumber,
                    $analysis['reason']
                ));
                continue;
            }

            $this->line(sprintf(
                '[FIX ] %s | %s -> %s | affected: %s',
                $jobNumber,
                implode(', ', $analysis['current_statuses']),
                $analysis['target_status'],
                implode(', ', $analysis['affected_labels'])
            ));

            if ($apply) {
                DB::transaction(function () use ($analysis, &$affectedRows) {
                    foreach ($analysis['jobs_to_update'] as $job) {
                        $updateData = [
                            'status' => $analysis['target_status'],
                            'updated_by' => auth()->id() ?? 1,
                        ];

                        if (in_array($analysis['target_status'], ['assign_material', 'barang_dipersiapkan', 'barang_siap_diambil'], true)) {
                            $updateData['material_checked'] = false;
                            $updateData['material_checked_at'] = null;
                        }

                        $job->update($updateData);
                        $affectedRows++;
                    }
                });

                Log::info('RepairGroupedJobMaterialStatuses: repaired grouped job statuses', [
                    'job_number' => $jobNumber,
                    'target_status' => $analysis['target_status'],
                    'updated_job_ids' => collect($analysis['jobs_to_update'])->pluck('id')->all(),
                ]);
            }

            $updatedGroups++;
        }

        $this->newLine();
        $this->info('Summary');
        $this->line('Checked groups: ' . $groups->count());
        $this->line('Repairable groups: ' . $updatedGroups);
        $this->line('Skipped groups: ' . $skippedGroups);
        $this->line('Affected rows: ' . ($apply ? $affectedRows : 'dry-run'));

        return self::SUCCESS;
    }

    protected function loadJobGroups($jobNumbers, int $limit)
    {
        $query = JobSchedule::query()
            ->with('room')
            ->whereNotNull('job_number')
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled');

        if ($jobNumbers->isNotEmpty()) {
            $query->whereIn('job_number', $jobNumbers->all());
        }

        $jobs = $query
            ->orderBy('job_number')
            ->orderBy('id')
            ->get()
            ->groupBy('job_number');

        if ($jobNumbers->isEmpty()) {
            $jobs = $jobs->filter(fn ($group) => $group->count() > 1);
        }

        if ($jobNumbers->isEmpty()) {
            $jobs = $jobs->take($limit);
        }

        return $jobs;
    }

    protected function analyzeGroup($jobs, bool $includeAdvanced): array
    {
        $jobIds = $jobs->pluck('id');
        $currentStatuses = $jobs->pluck('status')->unique()->values()->all();
        $advancedStatuses = ['teknisi_tiba_dilokasi', 'in_progress', 'teknisi_sedang_pengerjaan', 'teknisi_selesai_pengerjaan', 'done_job', 'completed'];

        $jobAssignMaterialIssues = JobAssignMaterialIssue::with(['materialIssue.items'])
            ->whereHas('jobAssignSchedule', function ($query) use ($jobIds) {
                $query->whereIn('job_schedule_id', $jobIds);
            })
            ->get();

        $materialIssueNumbers = $jobAssignMaterialIssues
            ->pluck('materialIssue.issue_number')
            ->filter()
            ->unique()
            ->values();

        $inventoryIssuings = $materialIssueNumbers->isNotEmpty()
            ? InventoryIssuing::whereIn('reference_no', $materialIssueNumbers->all())->get()
            : collect();

        $targetStatus = $this->determineTargetStatus($jobs, $jobAssignMaterialIssues, $inventoryIssuings);
        if (!$targetStatus) {
            return [
                'repairable' => false,
                'reason' => 'No material lifecycle state found',
                'current_statuses' => $currentStatuses,
            ];
        }

        if (count($currentStatuses) === 1 && $currentStatuses[0] === $targetStatus) {
            return [
                'repairable' => false,
                'reason' => 'Already consistent',
                'current_statuses' => $currentStatuses,
            ];
        }

        if (!$includeAdvanced && $jobs->contains(fn ($job) => in_array($job->status, $advancedStatuses, true))) {
            return [
                'repairable' => false,
                'reason' => 'Contains advanced statuses; rerun with --include-advanced if intentional',
                'current_statuses' => $currentStatuses,
            ];
        }

        $jobsToUpdate = $jobs->filter(function ($job) use ($targetStatus, $advancedStatuses, $includeAdvanced) {
            if (!$includeAdvanced && in_array($job->status, $advancedStatuses, true)) {
                return false;
            }

            return $job->status !== $targetStatus;
        })->values();

        if ($jobsToUpdate->isEmpty()) {
            return [
                'repairable' => false,
                'reason' => 'No eligible rows need updating',
                'current_statuses' => $currentStatuses,
            ];
        }

        return [
            'repairable' => true,
            'target_status' => $targetStatus,
            'current_statuses' => $currentStatuses,
            'jobs_to_update' => $jobsToUpdate,
            'affected_labels' => $jobsToUpdate->map(fn ($job) => $this->formatJobLabel($job))->all(),
        ];
    }

    protected function determineTargetStatus($jobs, $jobAssignMaterialIssues, $inventoryIssuings): ?string
    {
        if ($inventoryIssuings->contains(fn ($issuing) => $issuing->status === 'sent')) {
            $seedJob = $jobs->first();
            return in_array(strtolower($seedJob->type ?? ''), ['install', 'install_free', 'pemasangan'], true)
                ? 'teknisi_tiba_dilokasi'
                : 'barang_diambil';
        }

        if ($inventoryIssuings->contains(fn ($issuing) => $issuing->status === 'processed')) {
            return 'barang_siap_diambil';
        }

        if ($inventoryIssuings->contains(fn ($issuing) => $issuing->status === 'pending')) {
            return 'barang_dipersiapkan';
        }

        $hasPreparedMaterialItems = $jobAssignMaterialIssues->contains(function ($assignMaterialIssue) {
            return $assignMaterialIssue->materialIssue
                && $assignMaterialIssue->materialIssue->items
                    ->where('job_assign_schedule_id', $assignMaterialIssue->job_assign_schedule_id)
                    ->isNotEmpty();
        });

        if ($hasPreparedMaterialItems) {
            return 'barang_dipersiapkan';
        }

        if ($jobAssignMaterialIssues->isNotEmpty()) {
            return 'assign_material';
        }

        return null;
    }

    protected function formatJobLabel(JobSchedule $job): string
    {
        return sprintf(
            '%s[%s]',
            $job->room_name ?? $job->room?->room_name ?? ('Job ID ' . $job->id),
            $job->status
        );
    }
}
