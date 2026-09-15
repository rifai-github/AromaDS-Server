<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use ReflectionClass;
use Tests\TestCase;

/**
 * Kolom sumber kontrak Catalyst bernama `SQNo`, bukan `SqNo`.
 *
 * Baris sumber diambil dengan SELECT * lalu di-cast jadi array, jadi kuncinya
 * memakai ejaan kolom persis seperti di SQL Server — dan kunci array PHP
 * case-sensitive. SQL Server sendiri case-insensitive, sehingga
 * `->select('SqNo')` tetap mengembalikan data dan salah ejaan ini tidak pernah
 * terlihat saat dicek manual; nilainya hanya diam-diam jadi null.
 *
 * Akibatnya di produksi: dari 10.903 baris MKTContractHd yang SEMUANYA punya
 * SQNo, hanya 42 kontrak yang punya contracts.quotation_id — sisanya kebetulan
 * cocok lewat contract_number. Panel "Quotation Information" di detail kontrak
 * jadi kosong untuk hampir semua kontrak hasil import (dilaporkan QA 14 Sep 2026).
 */
class CatalystContractSqNoColumnCaseTest extends TestCase
{
    private function sourceValue(array $row, string ...$keys)
    {
        $importer = app(CatalystMasterDataImporter::class);
        $method = (new ReflectionClass($importer))->getMethod('sourceValue');
        $method->setAccessible(true);

        return $method->invoke($importer, $row, ...$keys);
    }

    public function test_reads_the_real_sqno_column_spelling(): void
    {
        $row = ['TransNmbr' => ' BDG-AG/24-11/0004', 'SQNo' => 'BDG-SQ/24-11/0004'];

        $this->assertSame('BDG-SQ/24-11/0004', $this->sourceValue($row, 'SQNo', 'SqNo'));
    }

    public function test_still_reads_the_legacy_spelling_when_that_is_what_the_source_has(): void
    {
        $row = ['TransNmbr' => ' BDG-AG/24-11/0004', 'SqNo' => 'BDG-SQ/24-11/0004'];

        $this->assertSame('BDG-SQ/24-11/0004', $this->sourceValue($row, 'SQNo', 'SqNo'));
    }

    public function test_falls_back_to_case_insensitive_lookup_for_any_other_spelling(): void
    {
        $row = ['transnmbr' => ' BDG-AG/24-11/0004', 'sqno' => 'BDG-SQ/24-11/0004'];

        $this->assertSame('BDG-SQ/24-11/0004', $this->sourceValue($row, 'SQNo', 'SqNo'));
        $this->assertSame(' BDG-AG/24-11/0004', $this->sourceValue($row, 'TransNmbr'));
    }

    public function test_returns_null_when_the_column_is_genuinely_absent(): void
    {
        $row = ['TransNmbr' => ' BDG-AG/24-11/0004'];

        $this->assertNull($this->sourceValue($row, 'SQNo', 'SqNo'));
    }

    public function test_exact_match_wins_over_case_insensitive_match(): void
    {
        // Kalau sumber benar-benar punya dua ejaan, yang diminta lebih dulu yang menang.
        $row = ['SQNo' => 'BENAR', 'sqno' => 'SALAH'];

        $this->assertSame('BENAR', $this->sourceValue($row, 'SQNo', 'SqNo'));
    }

    public function test_importer_no_longer_reads_the_raw_mis_cased_key(): void
    {
        $source = file_get_contents(app_path('Services/Imports/Catalyst/CatalystMasterDataImporter.php'));

        $this->assertStringNotContainsString("\$row['SqNo']", $source);
        $this->assertStringContainsString("\$this->sourceValue(\$row, 'SQNo', 'SqNo')", $source);
    }
}
