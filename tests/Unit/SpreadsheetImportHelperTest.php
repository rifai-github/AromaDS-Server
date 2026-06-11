<?php

namespace Tests\Unit;

use App\Services\Imports\SpreadsheetImportHelper;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SpreadsheetImportHelperTest extends TestCase
{
    private function csvUpload(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'sih') . '.csv';
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
