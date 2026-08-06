<?php

namespace App\Services\Finance;

use App\Models\Finance\Invoice;
use App\Models\Finance\TaxFileImportDetail;
use App\Models\TaxFileImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Reads the result file CoreTax hands back after a faktur pajak upload and
 * writes the issued numbers onto the matching invoices.
 *
 * The file is matched by NAMED columns rather than position: `Reference` carries
 * the invoice number we sent out in the export, and `TaxInvoiceStatus` says
 * whether DJP issued the faktur. An invoice that matches an APPROVED row is
 * promoted to tax_approved automatically — this replaces the old manual
 * TAX APPROVE button.
 */
class CoreTaxImportService
{
    /** Column headers that must be present for a file to be a CoreTax result. */
    private const REQUIRED_HEADERS = ['reference', 'taxinvoicestatus'];

    private const DELIMITER_CANDIDATES = [',', ';', "\t", '|'];

    /**
     * @return array{total: int, approved: int, unmatched: int, skipped: int}
     */
    public function process(TaxFileImport $import, string $relativePath): array
    {
        $fullPath = public_path('uploads/'.$relativePath);

        if (! file_exists($fullPath)) {
            throw new \RuntimeException('File not found: '.$relativePath);
        }

        $rows = $this->readRows($fullPath, $import);
        [$headerIndex, $columns] = $this->locateHeader($rows);

        $dataRows = array_slice($rows, $headerIndex + 1);
        $offset = $this->resolveColumnOffset($dataRows, $columns);

        $summary = ['total' => 0, 'approved' => 0, 'unmatched' => 0, 'skipped' => 0];

        foreach ($dataRows as $row) {
            $reference = trim((string) $this->cell($row, $columns, 'reference', $offset));

            // CoreTax pads the sheet with blank rows; they are not failures.
            if ($reference === '') {
                continue;
            }

            $summary['total']++;
            $this->handleRow($import, $row, $columns, $offset, $reference, $summary);
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

    private function handleRow(TaxFileImport $import, array $row, array $columns, int $offset, string $reference, array &$summary): void
    {
        $fakturNumber = trim((string) $this->cell($row, $columns, 'taxinvoicenumber', $offset));
        $status = strtoupper(trim((string) $this->cell($row, $columns, 'taxinvoicestatus', $offset)));
        $fakturDate = $this->parseDate($this->cell($row, $columns, 'taxinvoicedate', $offset));
        $vat = $this->parseAmount($this->cell($row, $columns, 'vat', $offset));

        $invoice = Invoice::where('invoice_number', $reference)->first();

        if (! $invoice) {
            $summary['unmatched']++;
            $this->writeDetail($import, $reference, $fakturNumber, $fakturDate, $vat, 'rejected',
                'Tidak ada invoice dengan nomor ini di sistem.');

            return;
        }

        if ($status !== Invoice::CORETAX_STATUS_APPROVED) {
            $summary['skipped']++;
            $this->writeDetail($import, $reference, $fakturNumber, $fakturDate, $vat, 'warning',
                'CoreTax belum menyetujui faktur ini (status: '.($status ?: 'kosong').').');

            return;
        }

        if ($invoice->invoice_status === Invoice::STATUS_CANCELLED) {
            $summary['skipped']++;
            $this->writeDetail($import, $reference, $fakturNumber, $fakturDate, $vat, 'warning',
                'Invoice sudah dibatalkan, tidak diubah.');

            return;
        }

        $promoted = $invoice->invoice_status === Invoice::STATUS_APPROVED;

        $invoice->update(array_merge([
            'coretax_faktur_number' => $fakturNumber,
            'coretax_faktur_date' => $fakturDate,
            'coretax_status' => $status,
        ], $promoted ? ['invoice_status' => Invoice::STATUS_TAX_APPROVED] : []));

        $note = $promoted
            ? 'Faktur Pajak '.$fakturNumber.' diterima dari CoreTax; invoice terbit (Tax Approved).'
            : 'Faktur Pajak '.$fakturNumber.' diterima dari CoreTax; status invoice tetap '.$invoice->invoice_status.'.';

        $invoice->invoiceActivities()->create([
            'activity_type' => 'updated',
            'notes' => $note.' Sumber: import '.$import->import_number.'.',
            'created_by' => Auth::id(),
        ]);

        $summary['approved']++;
        $this->writeDetail($import, $reference, $fakturNumber, $fakturDate, $vat, 'approved', $note);
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

    /**
     * Find the header row and map each known column name to its position.
     * CoreTax exports lead with unnamed columns, so positions cannot be assumed.
     *
     * @return array{0: int, 1: array<string, int>}
     */
    private function locateHeader(array $rows): array
    {
        foreach ($rows as $index => $row) {
            $columns = [];

            foreach ($row as $position => $value) {
                $key = $this->normalizeHeader((string) $value);

                if ($key !== '' && ! isset($columns[$key])) {
                    $columns[$key] = $position;
                }
            }

            $missing = array_diff(self::REQUIRED_HEADERS, array_keys($columns));

            if (empty($missing)) {
                return [$index, $columns];
            }
        }

        throw new \RuntimeException(
            'File ini tidak dikenali sebagai hasil CoreTax: kolom "Reference" dan "TaxInvoiceStatus" tidak ditemukan.'
        );
    }

    private function readRows(string $fullPath, TaxFileImport $import): array
    {
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            $sheets = \Maatwebsite\Excel\Facades\Excel::toArray([], $fullPath);

            return $sheets[0] ?? [];
        }

        // Try the configured delimiter first, then fall back — a file saved from
        // CoreTax may not use the delimiter the operator picked in the form.
        $configured = $import->delimiter === TaxFileImport::DELIMITER_TAB ? "\t" : ($import->delimiter ?: ',');
        $candidates = array_unique(array_merge([$configured], self::DELIMITER_CANDIDATES));

        $best = [];

        foreach ($candidates as $delimiter) {
            $rows = $this->readCsv($fullPath, $delimiter);

            try {
                $this->locateHeader($rows);

                return $rows;
            } catch (\RuntimeException) {
                if (count($rows) > count($best)) {
                    $best = $rows;
                }
            }
        }

        // No delimiter produced a recognisable header; hand back the best guess
        // so locateHeader() raises the explanatory error to the user.
        return $best;
    }

    private function readCsv(string $fullPath, string $delimiter): array
    {
        $rows = [];
        $handle = fopen($fullPath, 'r');

        if ($handle === false) {
            return $rows;
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * CoreTax writes ONE more leading blank column on the header row than on the
     * data rows, so header positions do not line up with the values underneath —
     * read straight through and "Reference" lands on the wrong field entirely.
     *
     * Rather than hard-code the shift, try each plausible offset and keep the one
     * where the status column actually reads like a status word.
     */
    private function resolveColumnOffset(array $dataRows, array $columns): int
    {
        $sample = null;

        foreach ($dataRows as $row) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) > 1) {
                $sample = $row;
                break;
            }
        }

        if ($sample === null) {
            return 0;
        }

        $candidates = [0];
        $widthGap = (max($columns) + 1) - count($sample);

        if ($widthGap > 0) {
            array_unshift($candidates, $widthGap);
        }

        foreach ($candidates as $offset) {
            $status = trim($this->cell($sample, $columns, 'taxinvoicestatus', $offset));
            $reference = trim($this->cell($sample, $columns, 'reference', $offset));

            if ($reference !== '' && preg_match('/^[A-Za-z]/', $status)) {
                return $offset;
            }
        }

        return 0;
    }

    private function cell(array $row, array $columns, string $key, int $offset = 0): string
    {
        $position = $columns[$key] ?? null;

        if ($position === null) {
            return '';
        }

        return (string) ($row[$position - $offset] ?? '');
    }

    private function normalizeHeader(string $value): string
    {
        // Strip the UTF-8 BOM that Excel leaves on the first header cell.
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseAmount(mixed $value): float
    {
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $clean === '' ? 0.0 : (float) $clean;
    }
}
