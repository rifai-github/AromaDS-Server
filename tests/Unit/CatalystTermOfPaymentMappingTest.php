<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use PHPUnit\Framework\TestCase;

/**
 * Mengunci mapping kode TermOfPayment Catalyst -> Term of Payment ADS.
 *
 * Nilai `top` harus sama persis dengan option_name yang di-seed
 * TermOfPaymentOptionsSeeder, kalau tidak dropdown SQ tidak akan
 * memilih apa-apa saat quotation hasil import dibuka.
 */
class CatalystTermOfPaymentMappingTest extends TestCase
{
    private function importer(): CatalystMasterDataImporter
    {
        return new CatalystMasterDataImporter;
    }

    public function test_after_service_codes_map_to_fixed_interval_terms(): void
    {
        $importer = $this->importer();

        foreach (['00' => 1, 'COD' => 1, '3xM' => 3, 'QUA' => 3, 'Q2Y' => 3, 'Q3Y' => 3] as $code => $months) {
            $term = $importer->mapCatalystTermOfPayment($code);

            $this->assertSame("{$months} bulan 1x", $term['top'], "TOP salah untuk kode {$code}");
            $this->assertSame('After Service', $term['timing'], "Timing salah untuk kode {$code}");
            $this->assertSame($months, $importer->catalystTopMonths($term, 12));
        }
    }

    public function test_single_advance_code_maps_to_tahunan(): void
    {
        $importer = $this->importer();
        $term = $importer->mapCatalystTermOfPayment('1x');

        $this->assertSame('Tahunan', $term['top']);
        $this->assertSame('Before Service', $term['timing']);
        $this->assertNull($importer->catalystTopMonths($term, 12));
    }

    public function test_multi_advance_codes_map_to_per_contract_period_terms(): void
    {
        $importer = $this->importer();

        foreach (['2x' => 2, '3x' => 3, '4x' => 4, '5xA' => 5, '6x' => 6, '8x' => 8] as $code => $count) {
            $term = $importer->mapCatalystTermOfPayment($code);

            $this->assertSame("{$count}x per periode contract", $term['top'], "TOP salah untuk kode {$code}");
            $this->assertSame('Before Service', $term['timing'], "Timing salah untuk kode {$code}");
        }
    }

    public function test_top_months_for_per_contract_period_divides_the_contract_duration(): void
    {
        $importer = $this->importer();
        $term = $importer->mapCatalystTermOfPayment('4x');

        $this->assertSame(3, $importer->catalystTopMonths($term, 12));
        $this->assertSame(6, $importer->catalystTopMonths($term, 24));
        // 10 bulan tidak habis dibagi 4 -> biarkan kosong daripada menyimpan angka salah.
        $this->assertNull($importer->catalystTopMonths($term, 10));
    }

    public function test_unknown_codes_fall_back_to_a_generic_parser(): void
    {
        $importer = $this->importer();

        $this->assertSame('6 bulan 1x', $importer->mapCatalystTermOfPayment('6M')['top']);
        $this->assertSame('12 bulan 1x', $importer->mapCatalystTermOfPayment('12 bulan')['top']);
        $this->assertSame('7x per periode contract', $importer->mapCatalystTermOfPayment('7xB')['top']);
    }

    public function test_blank_and_unreadable_codes_return_null(): void
    {
        $importer = $this->importer();

        $this->assertNull($importer->mapCatalystTermOfPayment(null));
        $this->assertNull($importer->mapCatalystTermOfPayment('  '));
        $this->assertNull($importer->mapCatalystTermOfPayment('NETTO'));
    }
}
