<?php

namespace App\Console\Commands;

use App\Models\JobAdvice;
use App\Models\JobSchedule;
use App\Services\DocumentNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rename Job Advice numbers whose branch prefix does not match the
 * Operational Area assignment of the building. Pairs with the fix in
 * DocumentNumberService::generate() that switched JA branch resolution
 * from the building's administrative city to the OperationalArea.
 *
 * Run with --dry-run first to inspect changes. The actual rename also
 * propagates the new number to job_schedules.reference_number rows that
 * still carry the old JA number.
 */
class FixJobAdviceBranchPrefix extends Command
{
    protected $signature = 'job-advices:fix-branch-prefix
                            {--dry-run : Show what would change without writing}
                            {--id= : Only process the JA with this id}
                            {--limit= : Process at most this many JAs}';

    protected $description = 'Realign existing Job Advice number prefixes to the Operational Area branch';

    public function handle(DocumentNumberService $documentNumberService): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $query = JobAdvice::query()->orderBy('id');
        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }
        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $jobAdvices = $query->get();
        if ($jobAdvices->isEmpty()) {
            $this->info('No Job Advices found for the given filter.');
            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s %d Job Advice(s)…',
            $isDryRun ? '[DRY-RUN] Inspecting' : 'Processing',
            $jobAdvices->count()
        ));

        $changed = 0;
        $skipped = 0;
        $failed = 0;
        $rows = [];

        foreach ($jobAdvices as $jobAdvice) {
            try {
                $expectedBranch = $documentNumberService->getBranchCodeFromOperationalArea(
                    null,
                    $jobAdvice->contract_id,
                    $jobAdvice->quotation_id,
                    null
                );

                if (!$expectedBranch) {
                    $skipped++;
                    $rows[] = [$jobAdvice->id, $jobAdvice->job_advice_number, '-', 'no operational area match'];
                    continue;
                }

                $currentNumber = $jobAdvice->job_advice_number;
                if (!preg_match('/^([A-Z]+)-(JA|COM)\/(\d{2})-(\d{2})\/(\d{4})$/', $currentNumber, $m)) {
                    $skipped++;
                    $rows[] = [$jobAdvice->id, $currentNumber, '-', 'unparseable number'];
                    continue;
                }

                [$_, $currentBranch, $typeCode, $year, $month, $seq] = $m;

                if ($currentBranch === $expectedBranch) {
                    $skipped++;
                    continue;
                }

                $newNumber = $this->allocateNewNumber($expectedBranch, $typeCode, $year, $month, $isDryRun);

                $rows[] = [$jobAdvice->id, $currentNumber, $newNumber, 'rename'];

                if ($isDryRun) {
                    $changed++;
                    continue;
                }

                DB::transaction(function () use ($jobAdvice, $currentNumber, $newNumber) {
                    JobAdvice::where('id', $jobAdvice->id)
                        ->update(['job_advice_number' => $newNumber]);

                    JobSchedule::where('reference_number', $currentNumber)
                        ->update(['reference_number' => $newNumber]);

                    Log::info('FixJobAdviceBranchPrefix: renamed', [
                        'job_advice_id' => $jobAdvice->id,
                        'old' => $currentNumber,
                        'new' => $newNumber,
                    ]);
                });

                $changed++;
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$jobAdvice->id, $jobAdvice->job_advice_number, '-', 'ERROR: ' . $e->getMessage()];
                Log::error('FixJobAdviceBranchPrefix failed', [
                    'job_advice_id' => $jobAdvice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($rows)) {
            $this->table(['JA ID', 'Old Number', 'New Number', 'Note'], $rows);
        }

        $this->info(sprintf(
            '%s — changed: %d, skipped: %d, failed: %d',
            $isDryRun ? 'DRY-RUN complete' : 'Done',
            $changed,
            $skipped,
            $failed
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Allocate the next sequence number for the (branch, type, year, month)
     * tuple. During --dry-run no claim is made on the sequence, so multiple
     * rows targeting the same prefix will all show the same projected
     * number — this is informational only.
     */
    private function allocateNewNumber(string $branchCode, string $typeCode, string $year, string $month, bool $isDryRun): string
    {
        $prefix = "{$branchCode}-{$typeCode}/{$year}-{$month}/";

        $query = DB::table('job_advices')
            ->where('job_advice_number', 'like', $prefix . '%')
            ->whereNotNull('job_advice_number')
            ->orderByRaw('CAST(SUBSTRING(job_advice_number, -4) AS UNSIGNED) DESC')
            ->orderBy('id', 'desc');

        if (!$isDryRun) {
            $query->lockForUpdate();
        }

        $last = $query->value('job_advice_number');
        $next = ($last && preg_match('/(\d{4})$/', $last, $m)) ? ((int) $m[1]) + 1 : 1;

        $candidate = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        // Guard against the unlikely case of a stray collision (e.g. number
        // hand-edited outside the sequence).
        while (DB::table('job_advices')->where('job_advice_number', $candidate)->exists()) {
            $next++;
            $candidate = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
