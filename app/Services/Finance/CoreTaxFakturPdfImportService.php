<?php

namespace App\Services\Finance;

use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceFile;
use App\Models\Finance\TaxFileImportDetail;
use App\Models\TaxFileImport;
use Illuminate\Support\Facades\Auth;

/**
 * Imports ONE individually-downloaded CoreTax "Output Tax Invoice" PDF — the
 * actual signed faktur pajak document for one invoice — as opposed to the
 * batched CSV/XLSX result file the export flow expects.
 *
 * A PDF supplies the faktur number the same way the CSV/XLSX path does,
 * matched on the "(Referensi: ...)" line CoreTax echoes back — the invoice
 * number we wrote into the export — via the same Invoice::applyCoreTaxFaktur()
 * both paths share. But a matched PDF is ALSO the faktur document itself, so
 * it gets archived onto the invoice's FILE(S) tab: the invoice detail page's
 * Faktur Pajak field already looks for a file whose description contains
 * "Faktur Pajak", so nothing else needs to change for it to show up there.
 *
 * Each PDF becomes its own tax_file_imports row with exactly one detail row
 * underneath (one row per FILE, not one row per batch) — so a failure here
 * only ever affects this one row; the caller loops over the upload and
 * creates a fresh TaxFileImport per file.
 */
class CoreTaxFakturPdfImportService
{
    public function __construct(private readonly CoreTaxFakturPdfParser $parser) {}

    public function process(TaxFileImport $import, string $originalName, string $fullPath): void
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
                $this->finish($import, $data, 'rejected',
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
                $this->finish($import, $data, 'warning', $result['note']);

                return;
            }

            $this->archiveOnInvoice($invoice, $originalName, $fullPath, $fakturNumber);

            $this->finish($import, $data, 'approved', $result['note']);
        } catch (\Throwable $e) {
            $this->finish($import, $data, 'rejected', "File \"{$originalName}\" gagal diproses: ".$e->getMessage());
        }
    }

    /**
     * Copy the faktur pajak PDF onto the invoice's FILE(S) tab. Skips
     * re-attaching if this exact faktur number is already archived there, so
     * re-importing the same PDF does not pile up duplicate copies.
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

    /**
     * Writes the one detail row this PDF produces, carrying every field the
     * parser managed to extract (best-effort ones fall back to null), and
     * rolls the outcome up onto the (1:1) parent import row.
     */
    private function finish(TaxFileImport $import, ?array $data, string $status, string $remarks): void
    {
        $data ??= [];

        TaxFileImportDetail::create([
            'tax_file_import_id' => $import->id,
            'invoice_number' => $data['reference'] ?? null ?: 'N/A',
            'tax_number' => $data['faktur_number'] ?? null ?: 'N/A',
            'buyer_npwp' => $data['buyer_npwp'] ?? null,
            'buyer_name' => $data['buyer_name'] ?? null,
            'tax_date' => $data['faktur_date'] ?? $import->import_date ?? now(),
            'tax_amount' => $data['ppn'] ?? 0,
            'dpp' => $data['dpp'] ?? null,
            'status' => $status,
            'remarks' => $remarks,
            'created_by' => Auth::id(),
        ]);

        $approved = $status === 'approved';

        $import->update([
            'total_records' => 1,
            'success_count' => $approved ? 1 : 0,
            'failed_count' => $approved ? 0 : 1,
            'success_rate' => $approved ? 100 : 0,
            'approval_success' => $approved ? 1 : 0,
            'not_approved' => $status === 'warning' ? 1 : 0,
            'rejected' => $status === 'rejected' ? 1 : 0,
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }
}
