<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use PHPUnit\Framework\TestCase;

class CatalystNitkuNormalizationTest extends TestCase
{
    public function test_it_extracts_a_six_digit_branch_code_from_catalyst_nitku(): void
    {
        $importer = new class extends CatalystMasterDataImporter
        {
            public function normalizeNitku($value): ?string
            {
                return $this->normalizeNitkuBranchCode($value);
            }
        };

        $this->assertSame('000000', $importer->normalizeNitku('0010016087904005000000'));
        $this->assertSame('123456', $importer->normalizeNitku('0010016087904005123456'));
        $this->assertSame('000123', $importer->normalizeNitku('000123'));
        $this->assertSame('012345', $importer->normalizeNitku('12.345'));
        $this->assertNull($importer->normalizeNitku(null));
        $this->assertNull($importer->normalizeNitku('N/A'));
    }
}
