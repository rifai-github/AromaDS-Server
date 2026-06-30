<?php

namespace Tests\Unit;

use App\Services\Imports\SpreadsheetImportHelper;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SpreadsheetImportHelperTest extends TestCase
{
    private function csvUpload(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'sih').'.csv';
        file_put_contents($path, $contents);

        // test mode = true so the file does not need to be a real HTTP upload.
        return new UploadedFile($path, 'data.csv', 'text/csv', null, true);
    }

    public function test_parses_csv_into_header_keyed_rows(): void
    {
        $rows = SpreadsheetImportHelper::parse($this->csvUpload(
            "serial_number,status\nSN-1,ready\nSN-2,on_hand\n"
        ));

        $this->assertCount(2, $rows);
        $this->assertSame(['serial_number' => 'SN-1', 'status' => 'ready'], $rows[0]);
        $this->assertSame(['serial_number' => 'SN-2', 'status' => 'on_hand'], $rows[1]);
    }

    public function test_strips_utf8_bom_from_first_header(): void
    {
        $rows = SpreadsheetImportHelper::parse($this->csvUpload(
            "\xEF\xBB\xBFname,phone\nPT Contoh,021\n"
        ));

        $this->assertArrayHasKey('name', $rows[0]);
        $this->assertSame('PT Contoh', $rows[0]['name']);
    }

    public function test_normalizes_header_keys_from_excel_friendly_labels(): void
    {
        $rows = SpreadsheetImportHelper::parse($this->csvUpload(
            "Product SKU,Required-Date, Item Notes \nDISW300W,29 Jun 2026,Catatan item\n"
        ));

        $this->assertSame('DISW300W', $rows[0]['product_sku']);
        $this->assertSame('29 Jun 2026', $rows[0]['required_date']);
        $this->assertSame('Catatan item', $rows[0]['item_notes']);
    }

    public function test_parses_xlsx_upload_even_when_temporary_path_has_no_extension(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['required_date', 'reason', 'product_sku', 'quantity'],
            ['29 Jun 2026', 'Restock', 'DISW300W', 10],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'sih');
        (new Xlsx($spreadsheet))->save($path);

        $rows = SpreadsheetImportHelper::parse(
            new UploadedFile($path, 'template-import-inventory-request.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true)
        );

        $this->assertSame('29 Jun 2026', $rows[0]['required_date']);
        $this->assertSame('Restock', $rows[0]['reason']);
        $this->assertSame('DISW300W', $rows[0]['product_sku']);
        $this->assertSame('10', $rows[0]['quantity']);
    }

    public function test_skips_fully_empty_rows_and_pads_short_rows(): void
    {
        $rows = SpreadsheetImportHelper::parse($this->csvUpload(
            "a,b,c\n1,2,3\n,,\nx\n"
        ));

        // The all-empty row is dropped; the short row "x" is padded.
        $this->assertCount(2, $rows);
        $this->assertSame(['a' => '1', 'b' => '2', 'c' => '3'], $rows[0]);
        $this->assertSame(['a' => 'x', 'b' => '', 'c' => ''], $rows[1]);
    }
}
