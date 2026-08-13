<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseCatalystJobAdvices extends Command
{
    protected $signature = 'catalyst:diagnose-job-advices
                            {--batch= : Specific source_import_batches.id to inspect (defaults to the latest Catalyst batch)}
                            {--messages=20 : Max distinct failure/skip messages to show per step}';

    protected $description = 'Read-only diagnostic: show why job_advices/job_advice_rooms ended up empty or partial in a Catalyst import batch, using source_import_batches/source_import_logs (no source DB connection needed)';

    public function handle(): int
    {
        $batchId = $this->option('batch') ? (int) $this->option('batch') : null;

        $batch = $batchId
            ? DB::table('source_import_batches')->where('id', $batchId)->first()
            : DB::table('source_import_batches')->where('source_system', 'catalyst')->orderByDesc('id')->first();

        if (!$batch) {
            $this->error($batchId ? "Batch #$batchId not found." : 'No Catalyst import batch found at all.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Batch #%d | mode=%s | status=%s | started=%s | finished=%s',
            $batch->id,
            $batch->mode,
            $batch->status,
            $batch->started_at,
            $batch->finished_at
        ));

        $summary = json_decode((string) $batch->summary, true) ?: [];
        $steps = $summary['steps'] ?? [];

        $steppedRan = json_decode((string) $batch->steps, true) ?: [];
        foreach (['job_advices', 'job_advice_rooms'] as $step) {
            if (!in_array($step, $steppedRan, true)) {
                $this->warn("Step \"$step\" was NOT part of this batch's step list at all.");
                continue;
            }

            $stats = $steps[$step]['stats'] ?? null;
            if (!$stats) {
                $this->warn("Step \"$step\" was requested but has no recorded stats (batch may have failed before reaching it).");
                continue;
            }

            $this->table(
                ["Step: $step", 'Count'],
                [
                    ['processed', $stats['processed'] ?? 0],
                    ['inserted', $stats['inserted'] ?? 0],
                    ['updated', $stats['updated'] ?? 0],
                    ['skipped', $stats['skipped'] ?? 0],
                    ['failed', $stats['failed'] ?? 0],
                ]
            );

            $messages = DB::table('source_import_logs')
                ->where('batch_id', $batch->id)
                ->where('step', $step)
                ->select('message', DB::raw('COUNT(*) as cnt'))
                ->groupBy('message')
                ->orderByDesc('cnt')
                ->limit(max((int) $this->option('messages'), 1))
                ->get();

            if ($messages->isEmpty()) {
                $this->line("No skip/fail messages logged for \"$step\" in this batch.");
            } else {
                $this->table(
                    ["Message for $step", 'Count'],
                    $messages->map(fn ($row) => [$row->message, $row->cnt])->all()
                );
            }

            $this->newLine();
        }

        $localJobAdviceCount = DB::table('job_advices')->count();
        // job_advices() syncs from MKTContractJobOut (not MKTContractHd) - that's the
        // ContractNo/job-out feed, distinct from the MKTContractHd feed that "contracts" uses.
        $mappedJobAdviceCount = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MKTContractJobOut')
            ->where('target_table', 'job_advices')
            ->count();

        $this->line('Total rows currently in local job_advices table: ' . $localJobAdviceCount);
        $this->line('Total source_import_maps rows pointing at job_advices (any source_table): ' . $mappedJobAdviceCount);

        $contractMapCount = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MKTContractHd')
            ->where('target_table', 'contracts')
            ->count();
        $quotationMapCount = DB::table('source_import_maps')
            ->where('source_system', 'catalyst')
            ->where('source_table', 'MKTQuotationHd')
            ->where('target_table', 'quotations')
            ->count();

        $this->line('For reference - contracts mapped from MKTContractHd: ' . $contractMapCount);
        $this->line('For reference - quotations mapped from MKTQuotationHd: ' . $quotationMapCount);
        $this->line('job_advices resolves each MKTContractJobOut.ContractNo against these two maps first - if both are 0, every job_advices row will fail with "Contract or quotation mapping missing".');

        return self::SUCCESS;
    }
}
