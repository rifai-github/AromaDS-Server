<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use App\Models\UnitOnWall;
use Illuminate\Console\Command;

class BackfillUnitOnWallContractLinkage extends Command
{
    protected $signature = 'unit-on-wall:backfill-contract-linkage
        {--apply : Persist the backfill. Default is dry-run}
        {--limit= : Only process the first N candidate units}';

    protected $description = 'Fill unit_on_walls.contract_id / contract_room_id / install_job_schedule_id for units created before the linkage columns existed, by tracing the install job number recorded in their notes';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist.');
        }

        $query = UnitOnWall::query()
            ->whereNull('contract_id')
            ->orderBy('id');

        if ($limit) {
            $query->limit($limit);
        }

        $units = $query->get();

        $stats = [
            'candidates' => $units->count(),
            'linked' => 0,
            'no_job_number_in_notes' => 0,
            'job_not_found' => 0,
            'no_contract_on_job' => 0,
        ];

        $rows = [];

        foreach ($units as $unit) {
            $jobNumber = $this->extractInstallJobNumber($unit->notes);

            if (! $jobNumber) {
                $stats['no_job_number_in_notes']++;
                continue;
            }

            // withTrashed: the install job may have been soft-deleted, the unit it produced
            // is still physically on the wall and still belongs to that contract.
            $installJob = JobSchedule::withTrashed()
                ->with(['jobAdvice.rooms.contractRoom'])
                ->where('job_number', $jobNumber)
                ->first();

            if (! $installJob) {
                $stats['job_not_found']++;
                continue;
            }

            $contractId = $installJob->jobAdvice?->contract_id;

            if (! $contractId) {
                $stats['no_contract_on_job']++;
                continue;
            }

            $contractRoomId = $installJob->jobAdvice?->rooms
                ->first(fn ($jaRoom) => $unit->room_id
                    && (int) ($jaRoom->contractRoom?->room_id ?? 0) === (int) $unit->room_id)
                ?->contract_room_id;

            $rows[] = [
                $unit->id,
                $unit->serial_number ?: '-',
                $jobNumber,
                $contractId,
                $contractRoomId ?: '-',
                $installJob->id,
            ];

            if ($apply) {
                $unit->forceFill([
                    'contract_id' => $contractId,
                    'contract_room_id' => $contractRoomId,
                    'install_job_schedule_id' => $installJob->id,
                ])->save();
            }

            $stats['linked']++;
        }

        if ($rows) {
            $this->table(['Unit', 'Serial', 'Install Job', 'Contract', 'Contract Room', 'Job Schedule'], $rows);
        }

        $this->table(['Metric', 'Value'], [
            ['Candidates (contract_id null)', $stats['candidates']],
            ['Linked', $stats['linked']],
            ['Skipped - no install job in notes', $stats['no_job_number_in_notes']],
            ['Skipped - install job not found', $stats['job_not_found']],
            ['Skipped - job advice has no contract', $stats['no_contract_on_job']],
        ]);

        if (! $apply && $stats['linked'] > 0) {
            $this->warn('Dry run finished. Re-run with --apply to persist these links.');
        }

        return self::SUCCESS;
    }

    /**
     * Units auto-created on job completion carry
     * "Auto-created from Install Job SBY-IR/26-07/0016. JA: ..." in their notes.
     */
    private function extractInstallJobNumber(?string $notes): ?string
    {
        if (! $notes) {
            return null;
        }

        if (! preg_match('/Install Job\s+([A-Z0-9\-\/]+)/i', $notes, $matches)) {
            return null;
        }

        return rtrim(trim($matches[1]), '.');
    }
}
