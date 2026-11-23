<?php

namespace Tests\Unit;

use App\Http\Controllers\Operational\JobAssignMaterialIssueController;
use PHPUnit\Framework\TestCase;

/**
 * QA 23 Nov 2026 (UAT 17 Nov #10): BOM Material Assign membaca isi ml dari teks Package Size.
 * Dulu hanya "N ml"; "1000mili", "1.000 ml" dan "1 L" jatuh ke bom_quantity (biasanya 1).
 */
class PackagingSizeToMlTest extends TestCase
{
    public function test_reads_common_volume_notations(): void
    {
        $cases = [
            '250 ml' => 250.0,
            '250ml' => 250.0,
            '100 ML' => 100.0,
            '1000mili' => 1000.0,
            '1000 mili' => 1000.0,
            '1.000 ml' => 1000.0,
            '1 L' => 1000.0,
            '1,5 liter' => 1500.0,
            '2 Liter' => 2000.0,
        ];

        foreach ($cases as $name => $expected) {
            $this->assertSame($expected, JobAssignMaterialIssueController::packagingSizeToMl($name), $name);
        }
    }

    public function test_returns_zero_without_a_volume_unit(): void
    {
        $this->assertSame(0.0, JobAssignMaterialIssueController::packagingSizeToMl('Bottle'));
        $this->assertSame(0.0, JobAssignMaterialIssueController::packagingSizeToMl('Pcs'));
    }
}
