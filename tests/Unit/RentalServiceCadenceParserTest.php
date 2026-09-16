<?php

namespace Tests\Unit;

use App\Services\Operational\RentalServiceCadenceParser;
use PHPUnit\Framework\TestCase;

/**
 * Kadens service dibaca dari nama master rental.
 *
 * Nama-nama di bawah ini diambil apa adanya dari 337 baris master_rentals di
 * aroma_fresh_bootstrap.sql (hasil seed "Master Product.xlsx"). Dua ejaan yang
 * dipakai klien: "12 SVC / YR PCKG" (berapa kali setahun) dan "1 Bln 1x"
 * (setiap N bulan, M kali). Dari 337 rental, 230 punya salah satu ejaan itu;
 * 107 sisanya -- hampir semuanya lini Hand Sanitizer/Soap -- tidak menyebut
 * kadens sama sekali dan sengaja TIDAK ditebak.
 */
class RentalServiceCadenceParserTest extends TestCase
{
    private RentalServiceCadenceParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RentalServiceCadenceParser;
    }

    private function cadence(?string $name): ?array
    {
        $result = $this->parser->parse($name);

        return $result === null
            ? null
            : [$result['frequency_months'], $result['frequency_times_per_month']];
    }

    public function test_reads_services_per_year_spelling(): void
    {
        // 12x setahun = tiap bulan sekali.
        $this->assertSame([1, 1], $this->cadence('ADS 103 12 SVC / YR PCKG 100 ml'));
        // 6x setahun = tiap 2 bulan sekali.
        $this->assertSame([2, 1], $this->cadence('ADS 2500S 6 SVC / YR PCKG'));
        // Lebih dari 12x setahun = beberapa kali dalam sebulan.
        $this->assertSame([1, 2], $this->cadence('ADS 2000 24 SVC / YR PCKG'));
        $this->assertSame([1, 3], $this->cadence('ADS 2000 36 SVC / YR PCKG'));
    }

    public function test_reads_monthly_interval_spelling(): void
    {
        $this->assertSame([1, 1], $this->cadence('ADS 106 50 ml 1Bln 1x'));
        $this->assertSame([1, 1], $this->cadence('ADS 105 100ML 1 BLN 1X'));
        $this->assertSame([1, 1], $this->cadence('ADS W600 1BLN 1X 250ML'));
    }

    public function test_interval_spelling_wins_when_a_name_carries_both(): void
    {
        // "1 Bln 1x" menyebut intervalnya langsung, jadi lebih spesifik
        // daripada total setahun.
        $this->assertSame([2, 1], $this->cadence('ADS 105 12 SVC / YR PCKG 2 Bln 1x'));
    }

    public function test_names_without_a_cadence_are_left_alone(): void
    {
        $this->assertNull($this->cadence('Dispenser Hand Sanitizer 7100 SB3--'));
        $this->assertNull($this->cadence('Hand Sanitizer 250ml (7100SBL250-)'));
        $this->assertNull($this->cadence('PURE Shower Gel 1000 ml'));
        $this->assertNull($this->cadence('ADS 105 - 30 ml (Khusus pak Furton)'));
        $this->assertNull($this->cadence(''));
        $this->assertNull($this->cadence(null));
    }

    public function test_service_counts_without_a_whole_month_interval_are_refused(): void
    {
        // 5x setahun bukan interval bulat dan tidak punya padanan di
        // RentalServiceFrequency::getCommonFrequencies(); lebih baik kosong
        // daripada dibulatkan diam-diam.
        $this->assertNull($this->cadence('ADS 100 5 SVC / YR PCKG'));
        $this->assertNull($this->cadence('ADS 100 7 SVC / YR PCKG'));
        $this->assertNull($this->cadence('ADS 100 0 SVC / YR PCKG'));
    }

    public function test_ukuran_ml_tidak_ikut_terbaca_sebagai_kadens(): void
    {
        // "150 ml" atau "1000ml" tidak boleh dikira jumlah service.
        $this->assertNull($this->cadence('EC ADS L1000 300ml'));
        $this->assertNull($this->cadence('Enzyme 305 500ml'));
    }
}
