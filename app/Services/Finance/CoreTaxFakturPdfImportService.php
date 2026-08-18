<?php

namespace App\Services\Finance;

use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceFile;
use App\Models\Finance\TaxFileImportDetail;
use App\Models\TaxFileImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Imports individually-downloaded CoreTax "Output Tax Invoice" PDFs — the
 * actual signed faktur pajak document for one invoice — as opposed to the
 * batched CSV/XLSX result file the export flow expects.
 *
 * Each PDF supplies the faktur number the same way the CSV/XLSX path does,
 * matched on the "(Referensi: ...)" line CoreTax echoes back — the invoice
 * number we wrote into the export — via the same Invoice::applyCoreTaxFaktur()
 * both paths share. But a matched PDF is ALSO the faktur document itself, so
 * it gets archived onto the invoice's FILE(S) tab: the invoice detail page's
 * Faktur Pajak field already looks for a file whose description contains
 * "Faktur Pajak", so nothing else needs to change for it to show up there.
 *
 * One bad file (unreadable, wrong document, no matching invoice) is recorded
 * as a rejected row and does not stop the rest of the batch.
 */
class CoreTaxFakturPdfImportService
{
    public function __construct(private readonly CoreTaxFakturPdfParser $parser) {}

    /**
     * @param  array<int, array{original_name: string, full_path: string}>  $files
     * @return array{total: int, approved: int, unmatched: int, skipped: int}
     */
    public function process(TaxFileImport $import, array $files): array
    {
        $summary = ['total' => 0, 'approved' => 0, 'unmatched' => 0, 'skipped' => 0];

        foreach ($files as $file) {
            $summary['total']++;
            $this->handleFile($import, $file['original_name'], $file['full_path'], $summary);
        }

        $import->update([
            'total_records' => $summary['total'],
            'success_count' => $summary['approved'],
            'failed_count' => $summary['unmatched'] + $summary['skipped'],
            'success_rate' => $summary['total'] > 0
                ? round(($summary['approved'] / $summary['total']) * 100, 2)
                : 0,
            'approval_success' => $summary['approved'],
            'not_approved' => $summary['skipped'],
            'rejected' => $summary['unmatched'],
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        return $summary;
    }

    private function handleFile(TaxFileImport $import, string $originalName, string $fullPath, array &$summary): void
    {
        $data = null;

        try {
            $data = $this->parser->parse($fullPath);
            $reference = $data['reference'];
            $fakturNumber = $data['faktur_number'];

            if (blank($reference) || blank($fakturNumber)) {
                throw new \RuntimeException(
                    'kolom Referensi atau Nomor Seri Faktur Pajak tidak ditemukan — pastikan ini file '.
                    '"Output Tax Invoice" asli dari CoreTax, bukan hasil scan atau file lain.'
                );
            }

            $invoice = Invoice::where('invoice_number', $reference)->first();

            if (! $invoice) {
                $summary['unmatched']++;
                $this->writeDetail($import, $reference, $fakturNumber, $data['faktur_date'], $data['ppn'] ?? 0, 'rejected',
                    'Tidak ada invoice dengan nomor "'.$reference.'" di sistem.');

                return;
            }

            $result = $invoice->applyCoreTaxFaktur(
                $fakturNumber,
                $data['faktur_date'],
                Invoice::CORETAX_STATUS_APPROVED,
                'PDF Faktur Pajak ('.$originalName.'), import '.$import->import_number
            );

            if (! $result['applied']) {
                $summary['skipped']++;
                $this->writeDetail($import, $reference, $fakturNumber, $data['faktur_date'], $data['ppn'] ?? 0, 'warning', $result['note']);

                return;
            }

            $this->archiveOnInvoice($invoice, $originalName, $fullPath, $fakturNumber);

            $summary['approved']++;
            $this->writeDetail($import, $reference, $fakturNumber, $data['faktur_date'], $data['ppn'] ?? 0, 'approved', $result['note']);
        } catch (\Throwable $e) {
            $summary['unmatched']++;
            $this->writeDetail(
                $import,
                $data['reference'] ?? 'N/A',
                $data['faktur_number'] ?? 'N/A',
                $data['faktur_date'] ?? null,
                $data['ppn'] ?? 0,
                'rejected',
                "File \"{$originalName}\" gagal diproses: ".$e->getMessage()
            );
        }
    }

    /**
     * Copy the faktur pajak PDF onto the invoice's FILE(S) tab. Skips
     * re-attaching if this exact faktur number is already archived there, so
     * re-importing the same PDF (or a batch containing it) does not pile up
     * duplicate copies.
     */
    private function archiveOnInvoice(Invoice $invoice, string $originalName, string $sourcePath, string $fakturNumber): void
    {
        $marker = 'Faktur Pajak '.$fakturNumber;

        $alreadyArchived = $invoice->invoiceFiles()
            ->where('description', 'like', '%'.$marker.'%')
            ->exists();

        if ($alreadyArchived) {
            return;
        }

        $directory = 'uploads/invoices/'.$invoice->id;
        $fullDirectory = public_path($directory);

        if (! is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0755, true);
        }

        $storedName = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        copy($sourcePath, $fullDirectory.DIRECTORY_SEPARATOR.$storedName);

        InvoiceFile::create([
            'invoice_id' => $invoice->id,
            'file_name' => $originalName,
            'file_path' => $directory.'/'.$storedName,
            'file_type' => 'pdf',
            'description' => $marker.' (dari CoreTax)',
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    private function writeDetail(
        TaxFileImport $import,
        string $invoiceNumber,
        string $fakturNumber,
        ?Carbon $fakturDate,
        float $vat,
        string $status,
        string $remarks
    ): void {
        TaxFileImportDetail::create([
            'tax_file_import_id' => $import->id,
            'invoice_number' => $invoiceNumber,
            'tax_number' => $fakturNumber !== '' ? $fakturNumber : 'N/A',
            'tax_date' => $fakturDate ?? $import->import_date ?? now(),
            'tax_amount' => $vat,
            'status' => $status,
            'remarks' => $remarks,
            'created_by' => Auth::id(),
        ]);
    }
}
