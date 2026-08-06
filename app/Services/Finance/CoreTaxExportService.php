<?php

namespace App\Services\Finance;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTax;
use App\Models\Finance\Invoice;
use App\Models\Finance\InvoiceRentalDetail;
use App\Models\TaxFileExport;
use App\Models\TaxSetting;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * Builds the XLSX file that Finance uploads to CoreTax (impor faktur pajak keluaran).
 *
 * The layout mirrors the reference file that DJP has already accepted, so the sheet
 * names, header rows, END terminators and cell types below are deliberate — CoreTax
 * rejects the upload when any of them drift.
 *
 *   Sheet "Faktur"        one row per invoice, every cell stored as text
 *   Sheet "DetailFaktur"  one row per invoice line, linked back by the "Baris" number
 *   Sheet "E-Faktur-..."  legacy e-Faktur desktop layout (FK / LT / OF), kept for
 *                         the tax team's own cross-check
 */
class CoreTaxExportService
{
    /** Fixed column values — identical on every row of the reference file. */
    public const JENIS_FAKTUR = 'Normal';

    public const DEFAULT_KODE_TRANSAKSI = '04';

    public const JENIS_ID_PEMBELI = 'TIN';

    public const NEGARA_PEMBELI = 'IDN';

    public const NOMOR_DOKUMEN_PEMBELI = '-';

    /** CoreTax resolves buyer name/address from the TIN, so the file only carries a placeholder. */
    public const PEMBELI_PLACEHOLDER = 'A';

    public const BARANG_JASA = 'B';

    public const KODE_BARANG_JASA = '000000';

    public const NAMA_SATUAN_UKUR = 'UM.0024';

    /**
     * Transaction code 04 charges 12% against a reduced base ("DPP Nilai Lain"),
     * which works out to the effective PPN rate against the full DPP.
     */
    public const TARIF_PPN = 12;

    public const DEFAULT_NITKU = '000000';

    private const EXPORT_DIR = 'exports/tax-files';

    /**
     * Generate the CoreTax workbook for an export record.
     *
     * @return array{file_path: string, total_records: int, file_size: int}
     */
    public function generate(TaxFileExport $export): array
    {
        $invoices = $this->resolveInvoices($export);

        if ($invoices->isEmpty()) {
            throw new \RuntimeException('No approved invoices found for the selected export criteria.');
        }

        $seller = $this->resolveSeller();
        $rows = $invoices->map(fn (Invoice $invoice) => $this->mapInvoice($invoice))->values();

        // A Faktur row with no DetailFaktur line is rejected by CoreTax, so fail here
        // with the offending invoice numbers rather than hand the user a bad upload.
        $withoutLines = $rows->where(fn (array $row) => empty($row['lines']))->pluck('invoice_number');

        if ($withoutLines->isNotEmpty()) {
            throw new \RuntimeException(
                'These invoices have no rental detail lines to report: '
                .$withoutLines->take(10)->implode(', ')
                .($withoutLines->count() > 10 ? ' (+'.($withoutLines->count() - 10).' more)' : '')
            );
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $this->writeFakturSheet($spreadsheet, $rows, $seller);
        $this->writeDetailFakturSheet($spreadsheet, $rows);
        $this->writeLegacyEFakturSheet($spreadsheet, $rows, $export);

        $spreadsheet->setActiveSheetIndex(0);

        $relativePath = self::EXPORT_DIR.'/'.$export->export_number.'.xlsx';
        $fullPath = public_path('uploads/'.$relativePath);

        $directory = dirname($fullPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        (new XlsxWriter($spreadsheet))->save($fullPath);
        $spreadsheet->disconnectWorksheets();

        return [
            'file_path' => $relativePath,
            'total_records' => $rows->count(),
            'file_size' => filesize($fullPath) ?: 0,
        ];
    }

    /**
     * Invoices eligible for this export — either the explicitly picked ones or the whole period.
     */
    public function resolveInvoices(TaxFileExport $export): EloquentCollection
    {
        $invoiceIds = $export->getFilterParametersArray()['invoice_ids'] ?? [];

        $query = Invoice::with(['customer', 'invoiceRentalDetails'])
            ->where('invoice_status', Invoice::STATUS_APPROVED)
            ->whereNotNull('invoice_date');

        if (! empty($invoiceIds)) {
            $query->whereIn('id', $invoiceIds);
        } else {
            $query->whereBetween('invoice_date', [
                $export->period_from->toDateString(),
                $export->period_to->toDateString(),
            ]);
        }

        return $query->orderBy('invoice_date')->orderBy('invoice_number')->get();
    }

    /**
     * Flatten one invoice into the values every sheet needs, so the three sheets
     * cannot disagree with each other.
     */
    private function mapInvoice(Invoice $invoice): array
    {
        $customer = $invoice->customer;
        $rate = TaxSetting::getEffectivePpnRate($invoice->invoice_date);

        $lines = [];
        foreach ($invoice->invoiceRentalDetails as $detail) {
            $quantity = (float) ($detail->quantity ?: 0);
            $unitPrice = (float) ($detail->unit_price ?: 0);
            $dpp = (float) ($detail->total_price ?: ($quantity * $unitPrice));

            // A zero-value line still belongs in the file; a line with no quantity does not.
            if ($quantity <= 0) {
                continue;
            }

            // Code 04 taxes 12% of a reduced base rather than the effective rate of the full base.
            $dppNilaiLain = $dpp * ($rate / (self::TARIF_PPN / 100));

            $lines[] = [
                'name' => $this->buildItemName($detail),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'dpp' => $dpp,
                'dpp_nilai_lain' => $dppNilaiLain,
                'ppn' => $dppNilaiLain * (self::TARIF_PPN / 100),
            ];
        }

        $buyerNpwp = $this->normalizeNpwp($invoice->npwp_number ?: ($customer->npwp ?? ''));

        return [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date,
            'kode_transaksi' => $this->normalizeKodeTransaksi($invoice->tax_code),
            'fg_pengganti' => $this->resolveFgPengganti($invoice->tax_code),
            'tax_number' => $invoice->tax_number,
            'contract_number' => $invoice->contract_number,
            'buyer_npwp' => $buyerNpwp,
            'buyer_tku' => $buyerNpwp.$this->resolveNitku($invoice->customer_id),
            'buyer_name' => $customer->name ?? '',
            'buyer_address' => $this->resolveAddress($invoice, $customer),
            'lines' => $lines,
            'total_dpp' => array_sum(array_column($lines, 'dpp')),
            'total_ppn' => array_sum(array_column($lines, 'ppn')),
        ];
    }

    /**
     * Sheet 1 — one row per invoice. Row 1 carries the seller NPWP, headers sit on row 3.
     */
    private function writeFakturSheet(Spreadsheet $spreadsheet, Collection $rows, array $seller): void
    {
        $sheet = $spreadsheet->createSheet()->setTitle('Faktur');

        $headers = [
            'Baris', 'Tanggal Faktur', 'Jenis Faktur', 'Kode Transaksi', 'Keterangan Tambahan',
            'Dokumen Pendukung', 'Referensi', 'Cap Fasilitas', 'ID TKU Penjual', 'NPWP/NIK Pembeli',
            'Jenis ID Pembeli', 'Negara Pembeli', 'Nomor Dokumen Pembeli', 'Nama Pembeli',
            'Alamat Pembeli', 'Email Pembeli', 'ID TKU Pembeli',
        ];

        // Every cell on this sheet is text — leading zeros in NPWP and TKU must survive.
        $sheet->getStyle('A1:Q1000')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        $this->setText($sheet, 'C1', $seller['npwp']);

        foreach ($headers as $index => $header) {
            $this->setText($sheet, $this->column($index).'3', $header);
        }

        $rowNumber = 4;
        foreach ($rows as $index => $row) {
            $values = [
                (string) ($index + 1),
                $row['invoice_date']?->format('d/m/Y') ?? '',
                self::JENIS_FAKTUR,
                $row['kode_transaksi'],
                '',
                '',
                $row['invoice_number'],
                '',
                $seller['tku'],
                $row['buyer_npwp'],
                self::JENIS_ID_PEMBELI,
                self::NEGARA_PEMBELI,
                self::NOMOR_DOKUMEN_PEMBELI,
                self::PEMBELI_PLACEHOLDER,
                self::PEMBELI_PLACEHOLDER,
                '',
                $row['buyer_tku'],
            ];

            foreach ($values as $column => $value) {
                if ($value !== '') {
                    $this->setText($sheet, $this->column($column).$rowNumber, $value);
                }
            }

            $rowNumber++;
        }

        $this->setText($sheet, 'A'.$rowNumber, 'END');
    }

    /**
     * Sheet 2 — one row per invoice line. "Baris" points back at the Faktur sheet row.
     */
    private function writeDetailFakturSheet(Spreadsheet $spreadsheet, Collection $rows): void
    {
        $sheet = $spreadsheet->createSheet()->setTitle('DetailFaktur');

        $headers = [
            'Baris', 'Barang/Jasa', 'Kode Barang Jasa', 'Nama Barang/Jasa', 'Nama Satuan Ukur',
            'Harga Satuan', 'Jumlah Barang Jasa', 'Total Diskon', 'DPP', 'DPP Nilai Lain',
            'Tarif PPN', 'PPN', 'Tarif PPnBM', 'PPnBM',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($this->column($index).'1', $header);
        }

        $rowNumber = 2;
        foreach ($rows as $index => $row) {
            foreach ($row['lines'] as $line) {
                $sheet->setCellValue('A'.$rowNumber, $index + 1);
                $sheet->setCellValue('B'.$rowNumber, self::BARANG_JASA);
                $this->setText($sheet, 'C'.$rowNumber, self::KODE_BARANG_JASA);
                $sheet->setCellValue('D'.$rowNumber, $line['name']);
                $sheet->setCellValue('E'.$rowNumber, self::NAMA_SATUAN_UKUR);
                $sheet->setCellValue('F'.$rowNumber, $this->numeric($line['unit_price']));
                $sheet->setCellValue('G'.$rowNumber, $this->numeric($line['quantity']));
                $sheet->setCellValue('H'.$rowNumber, 0);
                $sheet->setCellValue('I'.$rowNumber, $this->numeric($line['dpp']));
                $sheet->setCellValue('J'.$rowNumber, $line['dpp_nilai_lain']);
                $sheet->setCellValue('K'.$rowNumber, self::TARIF_PPN);
                $sheet->setCellValue('L'.$rowNumber, $this->numeric($line['ppn']));

                $rowNumber++;
            }
        }

        $sheet->setCellValue('A'.$rowNumber, 'END');
    }

    /**
     * Sheet 3 — legacy e-Faktur desktop layout: three header rows describing the
     * FK (faktur), LT (lawan transaksi) and OF (objek faktur) record types, then
     * one FK + LT + OF group per invoice.
     */
    private function writeLegacyEFakturSheet(Spreadsheet $spreadsheet, Collection $rows, TaxFileExport $export): void
    {
        $title = 'E-Faktur-'.$export->period_from?->format('Y-m-d').'_'.$export->period_to?->format('Y-m-d').'_';
        $sheet = $spreadsheet->createSheet()->setTitle(mb_substr($title, 0, 31));

        $this->writeRow($sheet, 1, [
            'FK', 'KD_JENIS_TRANSAKSI', 'FG_PENGGANTI', 'NOMOR_FAKTUR', 'MASA_PAJAK', 'TAHUN_PAJAK',
            'TANGGAL_FAKTUR', 'NPWP', 'NAMA', 'ALAMAT_LENGKAP', 'JUMLAH_DPP', 'JUMLAH_PPN',
            'JUMLAH_PPNBM', 'ID_KETERANGAN_TAMBAHAN', 'FG_UANG_MUKA', 'UANG_MUKA_DPP',
            'UANG_MUKA_PPN', 'UANG_MUKA_PPNBM', 'REFERENSI', 'KODE_DOKUMEN_PENDUKUNG',
        ]);

        $this->writeRow($sheet, 2, [
            'LT', 'NPWP', 'NAMA', 'JALAN', 'BLOK', 'NOMOR', 'RT', 'RW', 'KECAMATAN',
            'KELURAHAN', 'KABUPATEN', 'PROPINSI', 'KODE_POS', 'NOMOR_TELEPON',
        ]);

        $this->writeRow($sheet, 3, [
            'OF', 'KODE_OBJEK', 'NAMA', 'HARGA_SATUAN', 'JUMLAH_BARANG', 'HARGA_TOTAL',
            'DISKON', 'DPP', 'PPN', 'TARIF_PPNBM', 'PPNBM',
        ]);

        $rowNumber = 4;
        foreach ($rows as $row) {
            $date = $row['invoice_date'];

            // FK — the invoice header.
            $sheet->setCellValue('A'.$rowNumber, 'FK');
            $this->setText($sheet, 'B'.$rowNumber, $row['kode_transaksi']);
            $sheet->setCellValue('C'.$rowNumber, $row['fg_pengganti']);
            if ($row['tax_number']) {
                $this->setText($sheet, 'D'.$rowNumber, $row['tax_number']);
            }
            $sheet->setCellValue('E'.$rowNumber, $date ? (int) $date->format('n') : '');
            $sheet->setCellValue('F'.$rowNumber, $date ? (int) $date->format('Y') : '');
            if ($date) {
                $sheet->setCellValue('G'.$rowNumber, ExcelDate::PHPToExcel($date));
                $sheet->getStyle('G'.$rowNumber)->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_DATE_XLSX14);
            }
            $this->setText($sheet, 'H'.$rowNumber, $row['buyer_npwp']);
            $sheet->setCellValue('I'.$rowNumber, $row['buyer_name']);
            $sheet->setCellValue('J'.$rowNumber, $row['buyer_address']);
            $sheet->setCellValue('K'.$rowNumber, $this->numeric($row['total_dpp']));
            $sheet->setCellValue('L'.$rowNumber, $this->numeric($row['total_ppn']));
            foreach (['M', 'N', 'O', 'P', 'Q', 'R'] as $column) {
                $sheet->setCellValue($column.$rowNumber, 0);
            }
            $this->setText($sheet, 'S'.$rowNumber, $row['invoice_number']);
            $rowNumber++;

            // LT — the counterparty.
            $sheet->setCellValue('A'.$rowNumber, 'LT');
            $this->setText($sheet, 'B'.$rowNumber, $row['buyer_npwp']);
            $sheet->setCellValue('C'.$rowNumber, $row['buyer_name']);
            $sheet->setCellValue('D'.$rowNumber, $row['buyer_address']);
            $this->setText($sheet, 'S'.$rowNumber, $row['invoice_number']);
            $rowNumber++;

            // OF — one row per line item.
            foreach ($row['lines'] as $line) {
                $sheet->setCellValue('A'.$rowNumber, 'OF');
                $this->setText($sheet, 'B'.$rowNumber, $row['contract_number'] ?? '');
                $sheet->setCellValue('C'.$rowNumber, $line['name']);
                $this->setText($sheet, 'D'.$rowNumber, $this->money($line['unit_price']));
                $sheet->setCellValue('E'.$rowNumber, $this->numeric($line['quantity']));
                $this->setText($sheet, 'F'.$rowNumber, $this->money($line['dpp']));
                $this->setText($sheet, 'G'.$rowNumber, $this->money(0));
                $this->setText($sheet, 'H'.$rowNumber, $this->money($line['dpp']));
                $this->setText($sheet, 'I'.$rowNumber, $this->money($line['ppn']));
                $sheet->setCellValue('J'.$rowNumber, 0);
                $sheet->setCellValue('K'.$rowNumber, 0);
                $this->setText($sheet, 'S'.$rowNumber, $row['invoice_number']);
                $rowNumber++;
            }
        }
    }

    /**
     * The selling company — NPWP and the 22-digit TKU id CoreTax expects.
     *
     * @return array{npwp: string, tku: string}
     */
    private function resolveSeller(): array
    {
        $company = Company::where('is_active', true)->orderBy('id')->first();

        $npwp = $this->normalizeNpwp($company->npwp ?? '');
        $nitku = $this->normalizeNitku($company->nitku ?? '');

        return [
            'npwp' => $npwp,
            'tku' => $npwp.$nitku,
        ];
    }

    /**
     * The buyer's place-of-business id, kept per customer in the tax settings.
     */
    private function resolveNitku(?int $customerId): string
    {
        if (! $customerId) {
            return self::DEFAULT_NITKU;
        }

        $nitku = CustomerTax::where('customer_id', $customerId)
            ->where('is_active', true)
            ->whereNotNull('nitku')
            ->orderByDesc('id')
            ->value('nitku');

        return $this->normalizeNitku($nitku ?? '');
    }

    private function resolveAddress(Invoice $invoice, ?Customer $customer): string
    {
        return $invoice->tax_address
            ?: ($customer->npwp_address ?? null)
            ?: $invoice->billing_address
            ?: ($customer->address ?? '');
    }

    private function buildItemName(InvoiceRentalDetail $detail): string
    {
        $parts = array_filter([
            $detail->rental_name,
            $detail->building_name,
            $detail->room_name,
        ], fn ($part) => filled($part));

        return implode(' - ', $parts);
    }

    private function normalizeNpwp(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }

    private function normalizeNitku(?string $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? self::DEFAULT_NITKU : str_pad($digits, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Invoice tax codes are stored either as 2 digits ("01") or in the legacy
     * 3-digit form ("010") where the trailing digit is the replacement flag.
     */
    private function normalizeKodeTransaksi(?string $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return self::DEFAULT_KODE_TRANSAKSI;
        }

        return substr(str_pad($digits, 2, '0', STR_PAD_LEFT), 0, 2);
    }

    private function resolveFgPengganti(?string $value): int
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return strlen($digits) >= 3 ? (int) $digits[2] : 0;
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /**
     * Keep whole amounts as integers so the sheet reads like the reference file
     * rather than showing a trailing ".0" on every quantity and price.
     */
    private function numeric(float $value): int|float
    {
        return floor($value) == $value ? (int) $value : $value;
    }

    private function column(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
    }

    private function setText(Worksheet $sheet, string $coordinate, string $value): void
    {
        $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);
    }

    private function writeRow(Worksheet $sheet, int $rowNumber, array $values): void
    {
        foreach ($values as $index => $value) {
            $sheet->setCellValue($this->column($index).$rowNumber, $value);
        }
    }
}
