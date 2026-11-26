<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Jawaban klien di spreadsheet struktur Catalyst (26 Nov 2026), dipakai untuk import ulang
 * 29 Nov 2026:
 *  - no. 2  baris rental yang sama di ruangan yang sama dijumlahkan Qty-nya (1 + 1 = 2);
 *  - no. 3  hanya kontrak Status P, masih berjalan per 1 Des 2026, belum di-renew, bukan TEST;
 *  - no. 7  TransNmbr berawalan "-" = record tidak terpakai;
 *  - no. 8  Sales2 (sales pengganti) dipakai bila terisi;
 *  - no. 11 CustPONo dibawa sebagai nomor PO.
 */
class CatalystContractClientFilterTest extends TestCase
{
    private function importer(?array $eligible = null): CatalystMasterDataImporter
    {
        $importer = (new ReflectionClass(CatalystMasterDataImporter::class))->newInstanceWithoutConstructor();

        if ($eligible !== null) {
            $cache = new ReflectionProperty(CatalystMasterDataImporter::class, 'eligibleContractNumbersCache');
            $cache->setAccessible(true);
            $cache->setValue($importer, array_fill_keys($eligible, true));
        }

        return $importer;
    }

    private function invokeImporter(CatalystMasterDataImporter $importer, string $method, ...$args)
    {
        $reflection = new ReflectionMethod(CatalystMasterDataImporter::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($importer, ...$args);
    }

    private function row(array $overrides = []): array
    {
        return array_merge([
            'TransNmbr' => 'ADS-AG/26-10/0074',
            'Building' => 'B01',
            'FloorCode' => '01',
            'RoomCode' => 'R01',
            'Room' => 'Living Room',
            'Product' => 'L1000500',
            'Qty' => 1,
            'QtyFree' => 0,
            'PriceForex' => 100000,
        ], $overrides);
    }

    public function test_duplicate_rental_rows_in_the_same_room_are_summed_not_overwritten(): void
    {
        config(['catalyst-import.contract_filter' => true]);
        $importer = $this->importer(['ADS-AG/26-10/0074']);

        $rows = $this->invokeImporter($importer, 'groupContractRentalRows', [
            $this->row(),
            $this->row(['QtyFree' => 1]),
            $this->row(['Product' => 'OTHER-RENTAL']),
        ]);

        $this->assertCount(2, $rows);
        $this->assertSame(2.0, (float) $rows[0]['Qty']);
        $this->assertSame(1, $this->invokeImporter($importer, 'sourceQtyFree', $rows[0]));
        $this->assertSame(200000.0, (float) $rows[0]['__total_price']);
        $this->assertSame('ADS-AG/26-10/0074||B01||01||R01||Living Room||L1000500', $rows[0]['__group_key']);
        $this->assertSame(1.0, (float) $rows[1]['Qty']);
    }

    public function test_same_rental_in_another_room_stays_a_separate_row(): void
    {
        config(['catalyst-import.contract_filter' => true]);
        $importer = $this->importer(['ADS-AG/26-10/0074']);

        $rows = $this->invokeImporter($importer, 'groupContractRentalRows', [
            $this->row(),
            $this->row(['RoomCode' => 'R02', 'Room' => 'Lobby']),
        ]);

        $this->assertCount(2, $rows);
    }

    public function test_price_is_taken_from_a_priced_row_when_the_first_row_is_free(): void
    {
        config(['catalyst-import.contract_filter' => true]);
        $importer = $this->importer(['ADS-AG/26-10/0074']);

        $rows = $this->invokeImporter($importer, 'groupContractRentalRows', [
            $this->row(['Qty' => 0, 'QtyFree' => 1, 'PriceForex' => 0]),
            $this->row(['Qty' => 1, 'PriceForex' => 250000]),
        ]);

        $this->assertSame(250000.0, (float) $rows[0]['PriceForex']);
        $this->assertSame(250000.0, (float) $rows[0]['__total_price']);
        $this->assertSame(1.0, (float) $rows[0]['Qty']);
        $this->assertSame(1, $this->invokeImporter($importer, 'sourceQtyFree', $rows[0]));
    }

    public function test_rows_of_contracts_outside_the_client_filter_and_unused_rows_are_dropped(): void
    {
        config(['catalyst-import.contract_filter' => true]);
        $importer = $this->importer(['ADS-AG/26-10/0074']);

        $rows = $this->invokeImporter($importer, 'groupContractRentalRows', [
            $this->row(['TransNmbr' => 'MDN-AG/23-07/0004']),
            $this->row(['TransNmbr' => '-ADS-AG/26-10/0074']),
            $this->row(),
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame('ADS-AG/26-10/0074', $rows[0]['TransNmbr']);
        $this->assertTrue($this->invokeImporter($importer, 'contractPassesClientFilter', 'ADS-AG/26-10/0074'));
        $this->assertFalse($this->invokeImporter($importer, 'contractPassesClientFilter', 'MDN-AG/23-07/0004'));
        $this->assertFalse($this->invokeImporter($importer, 'contractPassesClientFilter', null));
    }

    public function test_filter_can_be_switched_off_by_config(): void
    {
        config(['catalyst-import.contract_filter' => false]);
        $importer = $this->importer();

        $this->assertNull($this->invokeImporter($importer, 'eligibleContractNumbers'));
        $this->assertTrue($this->invokeImporter($importer, 'contractPassesClientFilter', 'MDN-AG/23-07/0004'));
    }

    public function test_client_query_and_mappings_are_wired_into_the_contract_steps(): void
    {
        $source = file_get_contents(app_path('Services/Imports/Catalyst/CatalystMasterDataImporter.php'));

        // Query filter klien: semua syarat ada, dan NOT IN dijaga dari NULL.
        $this->assertStringContainsString("WHERE H.Status = 'P'", $source);
        $this->assertStringContainsString('DATEADD(DAY, -1, DATEADD(MONTH, H.ContractPeriod, H.TransDate)) >= ?', $source);
        $this->assertStringContainsString("WHERE X.Status IN ('P', 'T')", $source);
        $this->assertStringContainsString('AND G.OldContractNo IS NOT NULL', $source);
        $this->assertStringContainsString("ISNULL(C.CustName, '') NOT LIKE '%test%'", $source);

        $contracts = $this->stepBody('contracts');
        $this->assertStringContainsString('$this->contractPassesClientFilter($no)', $contracts);
        $this->assertStringContainsString("\$this->cleanString(\$row['Sales2'] ?? null) ?: \$this->cleanString(\$row['Sales'] ?? null)", $contracts);
        $this->assertStringContainsString("'po_number' => \$this->cleanString(\$row['CustPONo'] ?? null)", $contracts);

        foreach (['billing_groups', 'contract_buildings', 'contract_rooms'] as $step) {
            $this->assertStringContainsString('contractPassesClientFilter', $this->stepBody($step), "{$step}() tidak menyaring kontrak di luar filter klien.");
            $this->assertStringContainsString('isUnusedCatalystDetailRow', $this->stepBody($step), "{$step}() tidak membuang record berawalan \"-\".");
        }

        $this->assertStringContainsString('groupContractRentalRows', $this->stepBody('contract_rentals'));
        $this->assertStringNotContainsString('__index', $this->stepBody('contract_rentals'));
        $this->assertStringContainsString('isKnownCatalystContractOutsideFilter', $this->stepBody('job_advices'));
        $this->assertStringContainsString('isKnownCatalystContractOutsideFilter', $this->stepBody('job_advice_rooms'));
    }

    private function stepBody(string $step): string
    {
        $method = new ReflectionMethod(CatalystMasterDataImporter::class, $step);
        $lines = file($method->getFileName());

        return implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));
    }
}
