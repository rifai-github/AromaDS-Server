<?php

namespace App\Console\Commands;

use App\Models\InventoryIssuing;
use App\Models\JobAssignMaterialIssue;
use App\Services\OperationalAreaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairMaterialIssueServiceWarehouse extends Command
{
    protected $signature = 'operational:repair-material-issue-service-warehouse
        {--job= : Filter by job number}
        {--building= : Filter by building name}
        {--material-issue= : Filter by material issue number}
        {--include-issued : Include issued material issues}
        {--apply : Apply the repair}';

    protected $description = 'Repair material issue warehouse using the job building service area branch';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $rows = [];
        $planned = 0;
        $applied = 0;
        $skipped = 0;

        $issues = $this->queryIssues()->get()
            ->unique('material_issue_id')
            ->values();

        foreach ($issues as $assignment) {
            $plan = $this->planRepair($assignment);

            if ($plan['action'] !== 'plan') {
                $skipped++;
                $rows[] = $this->tableRow('SKIP', $assignment, $plan);
                continue;
            }

            $planned++;

            if ($apply) {
                DB::transaction(function () use ($assignment, $plan, &$applied) {
                    $assignment->materialIssue->update([
                        'warehouse_id' => $plan['target_warehouse']->id,
                    ]);

                    if ($plan['inventory_issuing']) {
                        $plan['inventory_issuing']->update([
                            'warehouse_id' => $plan['target_warehouse']->id,
                            'branch_id' => $plan['target_branch']->id,
                        ]);
                    }

                    $applied++;
                });
            }

            $rows[] = $this->tableRow($apply ? 'FIXED' : 'PLAN', $assignment, $plan);
        }

        $this->table([
            'Status',
            'Material Issue',
            'Job No',
            'Building',
            'Current Warehouse',
            'Target Warehouse',
            'Note',
        ], $rows);

        $this->line('Scanned issues : '.$issues->count());
        $this->line('Repair plans   : '.$planned);
        $this->line('Applied repairs: '.($apply ? $applied : 'dry-run'));
        $this->line('Skipped        : '.$skipped);

        if (! $apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function queryIssues()
    {
        $query = JobAssignMaterialIssue::with([
            'materialIssue.warehouse',
            'jobAssignSchedule.team.branch',
            'jobAssignSchedule.jobSchedule.building.city',
        ])->whereHas('materialIssue');

        if (! $this->option('include-issued')) {
            $query->whereHas('materialIssue', function ($materialIssueQuery) {
                $materialIssueQuery->where(function ($statusQuery) {
                    $statusQuery->whereNull('status')
                        ->orWhereNotIn(DB::raw('LOWER(TRIM(status))'), ['issued']);
                });
            });
        }

        if ($this->option('job')) {
            $job = trim((string) $this->option('job'));
            $query->whereHas('jobAssignSchedule.jobSchedule', function ($jobQuery) use ($job) {
                $jobQuery->where('job_number', $job);
            });
        }

        if ($this->option('building')) {
            $building = trim((string) $this->option('building'));
            $query->whereHas('jobAssignSchedule.jobSchedule.building', function ($buildingQuery) use ($building) {
                $buildingQuery->where('nama_gedung', 'like', "%{$building}%");
            });
        }

        if ($this->option('material-issue')) {
            $issueNumber = trim((string) $this->option('material-issue'));
            $query->whereHas('materialIssue', function ($materialIssueQuery) use ($issueNumber) {
                $materialIssueQuery->where('issue_number', $issueNumber);
            });
        }

        return $query->orderByDesc('id');
    }

    private function planRepair(JobAssignMaterialIssue $assignment): array
    {
        $materialIssue = $assignment->materialIssue;
        $jobSchedule = $assignment->jobAssignSchedule?->jobSchedule;
        $building = $jobSchedule?->building;

        if (! $materialIssue || ! $jobSchedule || ! $building) {
            return ['action' => 'skip', 'note' => 'missing material issue, job, or building'];
        }

        $targetBranch = OperationalAreaService::resolveServiceBranchForBuilding($building)
            ?: $assignment->jobAssignSchedule?->team?->branch;

        if (! $targetBranch) {
            return ['action' => 'skip', 'note' => 'target service branch was not found'];
        }

        $targetWarehouse = OperationalAreaService::resolveWarehouseForBranch($targetBranch);

        if (! $targetWarehouse) {
            return ['action' => 'skip', 'target_branch' => $targetBranch, 'note' => 'target branch has no active warehouse'];
        }

        if ((int) $materialIssue->warehouse_id === (int) $targetWarehouse->id) {
            return [
                'action' => 'skip',
                'target_branch' => $targetBranch,
                'target_warehouse' => $targetWarehouse,
                'note' => 'already uses target warehouse',
            ];
        }

        $inventoryIssuing = InventoryIssuing::where('reference_no', $materialIssue->issue_number)->first();
        if ($inventoryIssuing && ! in_array(strtolower(trim((string) $inventoryIssuing->status)), $this->repairableIssuingStatuses(), true)) {
            return [
                'action' => 'skip',
                'target_branch' => $targetBranch,
                'target_warehouse' => $targetWarehouse,
                'inventory_issuing' => $inventoryIssuing,
                'note' => "inventory issuing {$inventoryIssuing->issuing_number} is already {$inventoryIssuing->status}",
            ];
        }

        return [
            'action' => 'plan',
            'target_branch' => $targetBranch,
            'target_warehouse' => $targetWarehouse,
            'inventory_issuing' => $inventoryIssuing,
            'note' => 'move to service area warehouse',
        ];
    }

    private function repairableIssuingStatuses(): array
    {
        return ['pending', 'draft', 'approved', 'unprepared', 'unprepare', 'ready_to_issue', 'ready to issue'];
    }

    private function tableRow(string $status, JobAssignMaterialIssue $assignment, array $plan): array
    {
        return [
            $status,
            $assignment->materialIssue?->issue_number ?: '-',
            $assignment->jobAssignSchedule?->jobSchedule?->job_number ?: '-',
            $assignment->jobAssignSchedule?->jobSchedule?->building?->nama_gedung ?: '-',
            $assignment->materialIssue?->warehouse?->name ?: '-',
            $plan['target_warehouse']->name ?? '-',
            $plan['note'] ?? '-',
        ];
    }
}
