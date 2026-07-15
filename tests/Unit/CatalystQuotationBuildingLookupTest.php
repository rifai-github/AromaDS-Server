<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use PHPUnit\Framework\TestCase;

class CatalystQuotationBuildingLookupTest extends TestCase
{
    public function test_it_builds_and_caches_the_primary_building_lookup(): void
    {
        $importer = new class([(object) ['TransNmbr' => ' SQ//001 ', 'Building' => ' ', 'OldContractNo' => 'CT-20'], (object) ['TransNmbr' => 'SQ//001', 'Building' => ' BLD-A ', 'OldContractNo' => 'CT-10'], (object) ['TransNmbr' => 'SQ/001', 'Building' => 'BLD-B', 'OldContractNo' => 'CT-30'], (object) ['TransNmbr' => 'SQ/002', 'Building' => null, 'OldContractNo' => null], (object) ['TransNmbr' => ' SQ/002 ', 'Building' => 'BLD-2', 'OldContractNo' => 'CT-02'], (object) ['TransNmbr' => null, 'Building' => 'BLD-X', 'OldContractNo' => 'CT-X']]) extends CatalystMasterDataImporter
        {
            public int $sourceLoads = 0;

            private ?array $cachedRows = null;

            public function __construct(private array $rows) {}

            protected function sourceQuotationRentalRows(): array
            {
                if ($this->cachedRows !== null) {
                    return $this->cachedRows;
                }

                $this->sourceLoads++;

                return $this->cachedRows = $this->rows;
            }

            public function primaryBuildings(): array
            {
                return $this->sourceQuotationPrimaryBuildingByNumber();
            }

            public function oldContracts(): array
            {
                return $this->sourceQuotationOldContractByNumber();
            }

            public function normalizeDocument($value): ?string
            {
                return $this->normalizeDocumentNumber($value);
            }
        };

        $expected = [
            'SQ/001' => 'BLD-A',
            'SQ/002' => 'BLD-2',
        ];

        $expectedOldContracts = [
            'SQ/001' => 'CT-10',
            'SQ/002' => 'CT-02',
        ];

        $this->assertSame($expected, $importer->primaryBuildings());
        $this->assertSame($expected, $importer->primaryBuildings());
        $this->assertSame($expectedOldContracts, $importer->oldContracts());
        $this->assertSame($expectedOldContracts, $importer->oldContracts());
        $this->assertSame('JKT-SQ/24-07/0154', $importer->normalizeDocument(' jkt-sq//24-07/0154 '));
        $this->assertSame(1, $importer->sourceLoads);
    }
}
