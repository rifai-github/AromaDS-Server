<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use PHPUnit\Framework\TestCase;

class CatalystQuotationBuildingLookupTest extends TestCase
{
    public function test_it_builds_and_caches_the_primary_building_lookup(): void
    {
        $importer = new class([(object) ['TransNmbr' => ' SQ//001 ', 'Building' => ' '], (object) ['TransNmbr' => 'SQ//001', 'Building' => ' BLD-A '], (object) ['TransNmbr' => 'SQ/001', 'Building' => 'BLD-B'], (object) ['TransNmbr' => 'SQ/002', 'Building' => null], (object) ['TransNmbr' => ' SQ/002 ', 'Building' => 'BLD-2'], (object) ['TransNmbr' => null, 'Building' => 'BLD-X']]) extends CatalystMasterDataImporter
        {
            public int $sourceLoads = 0;

            public function __construct(private array $rows) {}

            protected function sourceQuotationRentalRows(): array
            {
                $this->sourceLoads++;

                return $this->rows;
            }

            public function primaryBuildings(): array
            {
                return $this->sourceQuotationPrimaryBuildingByNumber();
            }
        };

        $expected = [
            'SQ/001' => 'BLD-A',
            'SQ/002' => 'BLD-2',
        ];

        $this->assertSame($expected, $importer->primaryBuildings());
        $this->assertSame($expected, $importer->primaryBuildings());
        $this->assertSame(1, $importer->sourceLoads);
    }
}
