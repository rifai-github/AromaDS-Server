<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Finance\InvoiceGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairMissingInvoiceRentalDetails extends Command
{
    protected $signature = 'finance:repair-missing-invoice-rental-details
        {--invoice-id=* : Specific invoice id, repeatable}
        {--invoice-number=* : Specific invoice number, repeatable}
        {--include-finalized : Allow approved/sent/tax approved/paid invoices}
        {--apply : Apply the repair. Default is dry-run}';

    protected $description = 'Add missing rental rows to existing invoices using the completed IR/CSR trigger jobs for the invoice month';

    private const FINAL_STATUSES = ['approved', 'sent', 'tax_approved', 'paid'];

    public function handle(InvoiceGenerationService $invoiceGenerationService): int
    {
        $invoiceIds = collect($this->option('invoice-id'))->filter()->map(fn ($id) => (int) $id)->values();
        $invoiceNumbers = collect($this->option('invoice-number'))->filter()->map(fn ($number) => trim((string) $number))->values();
        $apply = (bool) $this->option('apply');

        if ($invoiceIds->isEmpty() && $invoiceNumbers->isEmpty()) {
            $this->error('Use --invoice-id or --invoice-number. This repair intentionally does not run without a specific target.');

            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('DRY RUN mode. No database changes will be made. Re-run with --apply to persist repairs.');
        }

        $invoices = Invoice::query()
            ->with('invoiceRentalDetails')
            ->where('invoice_status', '!=', Invoice::STATUS_CANCELLED)
            ->when($invoiceIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $invoiceIds->all()))
            ->when($invoiceNumbers->isNotEmpty(), fn ($query) => $query->whereIn('invoice_number', $invoiceNumbers->all()))
            ->orderBy('id')
            ->get();

        $rows = [];
        $scanned = 0;
        $planned = 0;
        $applied = 0;
        $skipped = 0;

        foreach ($invoices as $invoice) {
            if (! $this->option('include-finalized') && in_array((string) $invoice->invoice_status, self::FINAL_STATUSES, true)) {
                $skipped++;
                $rows[] = $this->row('SKIP', $invoice, null, 'finalized invoice; use --include-finalized only after finance approval');

                continue;
            }

            $expectedRows = $invoiceGenerationService->expectedRentalDetailsForInvoice($invoice);

            foreach ($expectedRows as $expected) {
                $scanned++;
                $exists = $invoice->invoiceRentalDetails->contains(function ($detail) use ($expected) {
                    return (int) $detail->master_rental_id === (int) $expected['master_rental_id']
                        && $this->normalize($detail->room_name) === $this->normalize($expected['room_name']);
                });

                if ($exists) {
                    $skipped++;
                    $rows[] = $this->row('SKIP', $invoice, $expected, 'rental row already exists');

                    continue;
                }

                $planned++;

                if ($apply) {
                    DB::transaction(function () use ($invoice, $expected, $invoiceGenerationService, &$applied) {
                        $payload = $expected;
                        $payload['created_by'] = auth()->id();

                        if (Schema::hasColumn('invoice_rental_details', 'updated_by')) {
                            $payload['updated_by'] = auth()->id();
                        }

                        $invoice->invoiceRentalDetails()->create($payload);
                        $invoiceGenerationService->refreshInvoiceTotals($invoice);
                        $applied++;
                    });
                }

                $rows[] = $this->row($apply ? 'FIXED' : 'PLAN', $invoice, $expected, 'add missing completed-job rental row');
            }
        }

        $this->table(
            ['Status', 'Invoice ID', 'Invoice No', 'Job No', 'Rental', 'Room', 'Total', 'Note'],
            $rows
        );
        $this->line('Scanned expected rows : '.$scanned);
        $this->line('Repair plans          : '.$planned);
        $this->line('Applied repairs       : '.($apply ? $applied : 'dry-run'));
        $this->line('Skipped               : '.$skipped);

        if (! $apply) {
            $this->line('Dry run only. Re-run with --apply after reviewing PLAN rows.');
        }

        return self::SUCCESS;
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function row(string $status, Invoice $invoice, ?array $expected, string $note): array
    {
        return [
            $status,
            $invoice->id,
            $invoice->invoice_number,
            $expected['job_no'] ?? '-',
            $expected['rental_name'] ?? '-',
            $expected['room_name'] ?? '-',
            $expected['total_price'] ?? 0,
            $note,
        ];
    }
}
