<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RepairPartialFollowUpPeriodContext extends Command
{
    protected $signature = 'jobs:repair-partial-followup-periods
                            {--contract-number= : Limit to a contract number}
                            {--job-id= : Specific follow-up job schedule ID}
                            {--apply : Apply the repair (default is dry-run)}
                            {--limit=200 : Limit rows when scanning}';

    protected $description = 'Repair P.Service/P.Invoice context on follow-up jobs created after outstanding partial completion';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $jobs = $this->loadFollowUpJobs();

        if (!$apply) {
            $this->info('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        if ($jobs->isEmpty()) {
            $this->warn('No partial follow-up jobs found to inspect.');

            return self::SUCCESS;
        }

        $rows = [];
        $plans = [];
        $skipped = 0;

        foreach ($jobs as $followUpJob) {
            $sourceJobNumber = $this->extractSourceJobNumber((string) $followUpJob->internal_notes);
            $sourceJob = $sourceJobNumber ? $this->findSourceJob($followUpJob, $sourceJobNumber) : null;

            if (!$sourceJob) {
                $skipped++;
                $rows[] = $this->row('SKIP', $followUpJob, null, null, 'source job not found from internal_notes');
                continue;
            }

            $changes = $this->plannedChanges($sourceJob, $followUpJob);
            if (empty($changes)) {
                $skipped++;
                $rows[] = $this->row('SKIP', $followUpJob, $sourceJob, $changes, 'already matches source period context');
                continue;
            }

            $plans[] = [$sourceJob, $followUpJob, $changes];
            $rows[] = $this->row($apply ? 'FIX' : 'PLAN', $followUpJob, $sourceJob, $changes, 'sync from outstanding source job');
        }

        $applied = 0;
        if ($apply) {
            foreach ($plans as [$sourceJob, $followUpJob, $changes]) {
                $followUpJob->forceFill($changes);
                $followUpJob->updated_by = auth()->id() ?: $sourceJob->updated_by ?: $sourceJob->created_by;
                $followUpJob->save();
                $applied++;
            }
        }

        $this->table(
            [
                'Status',
                'Follow-up ID',
                'Contract',
                'Room',
                'Current P.Service',
                'Target P.Service',
                'Current P.Invoice',
                'Target P.Invoice',
                'Source Job',
                'Note',
            ],
            $rows
        );

        $this->line('Scanned jobs   : ' . $jobs->count());
        $this->line('Repair plans   : ' . count($plans));
        $this->line('Applied repairs: ' . ($apply ? $applied : 'dry-run'));
        $this->line('Skipped        : ' . $skipped);

        if (!$apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function loadFollowUpJobs()
    {
        return JobSchedule::with(['room', 'jobScheduleRooms'])
            ->whereNull('job_number')
            ->where('internal_notes', 'like', 'Lanjutan dari Job %')
            ->whereNotIn('status', ['cancelled', 'done_job', 'completed', 'selesai'])
            ->when($this->option('contract-number'), function ($query) {
                $query->where('contract_number', trim((string) $this->option('contract-number')));
            })
            ->when($this->option('job-id'), function ($query) {
                $query->whereKey((int) $this->option('job-id'));
            })
            ->orderByDesc('id')
            ->limit(max((int) $this->option('limit'), 1))
            ->get();
    }

    private function extractSourceJobNumber(string $notes): ?string
    {
        if (preg_match('/Lanjutan dari Job\s+([A-Z0-9-]+\/\d{2}-\d{2}\/\d+)/i', $notes, $match)) {
            return $match[1];
        }

        if (preg_match('/Lanjutan dari Job\s+([^\s(]+)/i', $notes, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function findSourceJob(JobSchedule $followUpJob, string $sourceJobNumber): ?JobSchedule
    {
        return JobSchedule::where('job_number', $sourceJobNumber)
            ->when($followUpJob->job_advice_id, fn ($query) => $query->where('job_advice_id', $followUpJob->job_advice_id))
            ->when($followUpJob->building_id, fn ($query) => $query->where('building_id', $followUpJob->building_id))
            ->when($followUpJob->type, fn ($query) => $query->where('type', $followUpJob->type))
            ->orderByDesc('period')
            ->latest('id')
            ->first()
            ?: JobSchedule::where('job_number', $sourceJobNumber)->latest('id')->first();
    }

    private function plannedChanges(JobSchedule $sourceJob, JobSchedule $followUpJob): array
    {
        $changes = [];

        foreach ($this->contextColumns() as $column) {
            if (!Schema::hasColumn('job_schedules', $column)) {
                continue;
            }

            if ($followUpJob->{$column} != $sourceJob->{$column}) {
                $changes[$column] = $sourceJob->{$column};
            }
        }

        return $changes;
    }

    private function contextColumns(): array
    {
        return [
            'schedule_date',
            'period',
            'service_frequency',
            'service_period_type',
            'service_interval_days',
            'next_service_date',
            'reference_number',
            'job_reference_number',
            'day',
            'material_checked',
            'material_checked_at',
        ];
    }

    private function row(string $status, JobSchedule $followUpJob, ?JobSchedule $sourceJob, ?array $changes, string $note): array
    {
        $targetPeriod = array_key_exists('period', $changes ?? [])
            ? $changes['period']
            : ($sourceJob?->period ?? $followUpJob->period);

        $currentInvoice = $followUpJob->invoice_period;
        $targetInvoice = $this->calculateTargetInvoicePeriod($sourceJob, $followUpJob, $targetPeriod);

        return [
            $status,
            $followUpJob->id,
            $followUpJob->contract_number ?: '-',
            $followUpJob->jobScheduleRooms->pluck('room_name')->filter()->first()
                ?: ($followUpJob->getRawOriginal('room_name') ?: ($followUpJob->room?->room_name ?? '-')),
            $followUpJob->period ?? '-',
            $targetPeriod ?? '-',
            $currentInvoice ?? '-',
            $targetInvoice ?? '-',
            $sourceJob?->job_number ?? '-',
            $note,
        ];
    }

    private function calculateTargetInvoicePeriod(?JobSchedule $sourceJob, JobSchedule $followUpJob, $targetPeriod)
    {
        if (!$sourceJob || empty($targetPeriod)) {
            return $followUpJob->invoice_period;
        }

        $preview = $followUpJob->replicate();
        $preview->period = $targetPeriod;
        $preview->service_frequency = $sourceJob->service_frequency;
        $preview->setRelation('jobAdvice', $sourceJob->jobAdvice);

        return $preview->invoice_period;
    }
}
