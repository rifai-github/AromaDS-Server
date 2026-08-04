<?php

namespace App\Console\Commands;

use App\Models\Finance\Invoice;
use App\Services\DocumentNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Renames auto-generated invoice numbers whose branch prefix no longer
 * matches the contract's actual building location — the symptom of the
 * branch-location corruption fixed by branches:fix-corrupted-locations.
 * Run that command first (or confirm the branch data is already correct)
 * before renumbering invoices, since the expected prefix here is resolved
 * live from the current branches table.
 *
 * Only touches invoices that are still safe to renumber: draft status, not
 * printed, not emailed, no faktur pajak recorded, and no denormalized copy
 * in invoice_forms. Anything else is left untouched and reported so a human
 * can decide (a printed/emailed number is a real-world document reference).
 */
class FixInvoiceBranchPrefix extends Command
{
    protected $signature = 'invoices:fix-branch-prefix
                            {--contract-number=* : Only process invoices for this contract number, repeatable}
                            {--apply : Apply the fix. Default is dry-run}';

    protected $description = 'Realign auto-generated invoice number prefixes to the contract building\'s actual branch';

    public function handle(DocumentNumberService $documentNumberService): int
    {
        $apply = (bool) $this->option('apply');
        $contractNumbers = collect($this->option('contract-number'))->filter()->values();

        $query = Invoice::query()->whereNotNull('contract_id')->whereNotNull('invoice_number')->orderBy('id');
        if ($contractNumbers->isNotEmpty()) {
            $query->whereIn('contract_number', $contractNumbers);
        }

        $invoices = $query->get();

        $rows = [];
        $renamed = 0;
        $blocked = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($invoices as $invoice) {
            try {
                if (!preg_match('/^([A-Z]+)-(INV)\/(\d{2})-(\d{2})\/(\d{4})$/', $invoice->invoice_number, $m)) {
                    $skipped++;
                    continue;
                }
                [, $currentBranch, $typeCode, $year, $month] = $m;

                $expectedBranch = $documentNumberService->getBranchCodeFromContract($invoice->contract_id);
                if (!$expectedBranch || $expectedBranch === $currentBranch) {
                    $skipped++;
                    continue;
                }

                $blockers = $this->blockingReasons($invoice);
                if (!empty($blockers)) {
                    $blocked++;
                    $rows[] = [$invoice->id, $invoice->invoice_number, '-', 'BLOCKED: ' . implode(', ', $blockers)];
                    continue;
                }

                $newNumber = $this->allocateNewNumber($expectedBranch, $typeCode, $year, $month, $apply);
                $rows[] = [$invoice->id, $invoice->invoice_number, $newNumber, $apply ? 'renamed' : 'would rename'];

                if (!$apply) {
                    $renamed++;
                    continue;
                }

                DB::transaction(function () use ($invoice, $newNumber) {
                    $oldNumber = $invoice->invoice_number;
                    $invoice->update(['invoice_number' => $newNumber]);

                    Log::info('FixInvoiceBranchPrefix: renamed', [
                        'invoice_id' => $invoice->id,
                        'contract_number' => $invoice->contract_number,
                        'old' => $oldNumber,
                        'new' => $newNumber,
                    ]);
                });

                $renamed++;
            } catch (\Throwable $e) {
                $failed++;
                $rows[] = [$invoice->id, $invoice->invoice_number, '-', 'ERROR: ' . $e->getMessage()];
                Log::error('FixInvoiceBranchPrefix failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($rows)) {
            $this->table(['Invoice ID', 'Old Number', 'New Number', 'Note'], $rows);
        }

        $this->info(sprintf(
            '%s — %s: %d, blocked: %d, skipped (already correct/unparseable): %d, failed: %d',
            $apply ? 'Done' : 'DRY-RUN complete',
            $apply ? 'renamed' : 'would rename',
            $renamed,
            $blocked,
            $skipped,
            $failed
        ));

        if ($blocked > 0) {
            $this->warn('Blocked invoices were left untouched — they are printed/emailed/have tax documents, so renumbering needs a manual decision.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return string[] Reasons this invoice must not be auto-renamed.
     */
    private function blockingReasons(Invoice $invoice): array
    {
        $reasons = [];

        if ($invoice->invoice_status && $invoice->invoice_status !== Invoice::STATUS_DRAFT) {
            $reasons[] = "status={$invoice->invoice_status}";
        }
        if ($invoice->is_printed) {
            $reasons[] = 'printed';
        }
        if ($invoice->is_emailed) {
            $reasons[] = 'emailed';
        }
        if (!empty($invoice->faktur_pajak)) {
            $reasons[] = 'has faktur_pajak';
        }
        if (DB::table('invoice_forms')->where('invoice_number', $invoice->invoice_number)->exists()) {
            $reasons[] = 'has invoice_forms snapshot';
        }

        return $reasons;
    }

    /**
     * Allocate the next sequence number for the (branch, type, year, month)
     * tuple. During dry-run no claim is made on the sequence, so multiple
     * rows targeting the same prefix will all show the same projected
     * number — this is informational only.
     */
    private function allocateNewNumber(string $branchCode, string $typeCode, string $year, string $month, bool $apply): string
    {
        $prefix = "{$branchCode}-{$typeCode}/{$year}-{$month}/";

        $query = DB::table('invoices')
            ->where('invoice_number', 'like', $prefix . '%')
            ->whereNotNull('invoice_number')
            ->orderByRaw('CAST(SUBSTRING(invoice_number, -4) AS UNSIGNED) DESC')
            ->orderBy('id', 'desc');

        if ($apply) {
            $query->lockForUpdate();
        }

        $last = $query->value('invoice_number');
        $next = ($last && preg_match('/(\d{4})$/', $last, $m)) ? ((int) $m[1]) + 1 : 1;

        $candidate = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        while (DB::table('invoices')->where('invoice_number', $candidate)->exists()) {
            $next++;
            $candidate = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }
}
