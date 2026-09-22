<?php

namespace Tests\Feature;

use App\Services\Imports\Catalyst\CatalystMasterDataImporter;
use ReflectionClass;
use Tests\TestCase;

/**
 * Kadens service rental diambil dari Catalyst, bukan ditebak dari nama rental.
 *
 * "Master Product.xlsx" — sumber resmi rental sejak 24 Agu 2026 — hanya memuat kolom
 * A-F dan TIDAK memuat kadens service. Migrasi 2026_09_22_000001 menebaknya dari nama
 * rental, tapi hanya 230 dari 337 nama menyebutkannya; 107 sisanya (lini Hand
 * Sanitizer/Soap dan sejenisnya) tidak.
 *
 * Catalyst punya jawabannya untuk semuanya: per 22 Sep 2026 `MsProduct.FrequencyService`
 * terisi di SELURUH 357 baris RNT (1XM 345, 2B1x 7, 2XM 4, 3XM 1), dan 325 dari 337
 * rental_code lokal cocok dengan ProductCode-nya — menutup 97 rental yang namanya bungkam.
 *
 * Step `rental_service_frequency_links` mengisinya TANPA menghidupkan step `master_rentals`
 * yang dimatikan, karena kolom kadens ini memang tidak punya sumber di Excel sehingga tidak
 * ada data Excel yang bisa tertimpa.
 */
class CatalystRentalServiceFrequencyLinkTest extends TestCase
{
    private function importerSource(): string
    {
        return file_get_contents(
            app_path('Services/Imports/Catalyst/CatalystMasterDataImporter.php')
        );
    }

    private function stepBody(): string
    {
        $source = $this->importerSource();

        // Mulai dari docblock-nya, bukan dari tanda tangan method: alasan kenapa nama
        // rental menang atas Catalyst ditulis di situ dan ikut dikunci test.
        $start = strpos($source, 'Isi master_rentals.service_frequency_id dari MsProduct.FrequencyService.');
        $this->assertNotFalse($start, 'Docblock step rental_service_frequency_links() tidak ditemukan.');

        $this->assertNotFalse(
            strpos($source, 'protected function rental_service_frequency_links(): array'),
            'Step rental_service_frequency_links() tidak ditemukan.'
        );

        $end = strpos($source, 'protected function quotation_existing_contracts', $start);
        $this->assertNotFalse($end, 'Batas akhir step tidak ditemukan.');

        return substr($source, $start, $end - $start);
    }

    /**
     * @return array<int, string>
     */
    private function registeredSteps(): array
    {
        $importer = app(CatalystMasterDataImporter::class);
        $property = (new ReflectionClass($importer))->getProperty('steps');
        $property->setAccessible(true);

        return $property->getValue($importer);
    }

    private function resolve(string $catalystValue, array $lookup): ?int
    {
        $importer = app(CatalystMasterDataImporter::class);
        $reflection = new ReflectionClass($importer);

        $property = $reflection->getProperty('targetRentalServiceFrequencyLookup');
        $property->setAccessible(true);
        $property->setValue($importer, $lookup);

        $method = $reflection->getMethod('resolveRentalServiceFrequencyId');
        $method->setAccessible(true);

        return $method->invoke($importer, $catalystValue);
    }

    public function test_the_step_runs_without_re_enabling_the_disabled_rental_import(): void
    {
        $this->assertContains('rental_service_frequency_links', $this->registeredSteps());

        $this->assertNotContains(
            'rental_service_frequency_links',
            CatalystMasterDataImporter::DISABLED_STEPS,
            'Step ini harus tetap hidup.'
        );

        // Blok rental Catalyst harus tetap mati — hanya kolom kadens yang diisi.
        foreach (['master_rentals', 'rental_components', 'rental_details'] as $disabled) {
            $this->assertContains($disabled, CatalystMasterDataImporter::DISABLED_STEPS);
        }
    }

    /**
     * @testWith ["1XM", "1x1"]
     *           ["2XM", "1x2"]
     *           ["3XM", "1x3"]
     *           ["2B1X", "2x1"]
     *           ["2B1x", "2x1"]
     *           ["  1xm  ", "1x1"]
     */
    public function test_every_catalyst_cadence_code_maps_to_its_catalog_row(string $catalystValue, string $cadenceKey): void
    {
        // "2B1x" dengan x kecil adalah ejaan yang benar-benar ada di sumber (7 baris).
        $lookup = ['1x1' => 11, '1x2' => 12, '1x3' => 13, '2x1' => 14];

        $this->assertSame($lookup[$cadenceKey], $this->resolve($catalystValue, $lookup));
    }

    public function test_an_unknown_cadence_resolves_to_nothing_instead_of_guessing(): void
    {
        $lookup = ['1x1' => 11, '1x2' => 12, '1x3' => 13, '2x1' => 14];

        $this->assertNull($this->resolve('4XM', $lookup));
        $this->assertNull($this->resolve('', $lookup));
    }

    public function test_a_cadence_with_no_catalog_row_resolves_to_nothing(): void
    {
        // Katalog belum punya baris "tiap 2 bulan": kodenya dikenali tapi tidak ada
        // tujuannya, jadi harus null — bukan jatuh ke baris lain.
        $this->assertNull($this->resolve('2B1X', ['1x1' => 11]));
    }

    public function test_the_step_only_fills_rows_that_are_still_empty(): void
    {
        $body = $this->stepBody();

        // Pilihan manusia lewat UI Master Rental dan hasil migrasi tidak boleh ditimpa.
        $this->assertStringContainsString('if ($rental->service_frequency_id) {', $body);
        $this->assertStringContainsString("return ['action' => 'skipped'];", $body);
    }

    /**
     * Alasan "nama menang, Catalyst mengisi kekosongan" harus tetap tertulis.
     *
     * Kedua sumber dibandingkan untuk 325 rental yang kodenya cocok (22 Sep 2026):
     * 226 sepakat, 97 hanya diketahui Catalyst, 2 berbeda — dan pada dua-duanya nama
     * rental yang benar, karena 1XM mengisi 345 dari 357 baris sumber alias nilai default.
     * Tanpa catatan ini, "Catalyst kan sistem aslinya" terlihat seperti perbaikan.
     */
    public function test_the_reason_the_rental_name_outranks_catalyst_stays_written_down(): void
    {
        $body = $this->stepBody();

        $this->assertStringContainsString('ADS5000S-6-10', $body);
        $this->assertStringContainsString('VG880-2', $body);
        $this->assertStringContainsString('345 dari 357', $body);
    }

    public function test_the_step_touches_only_the_two_cadence_columns(): void
    {
        $body = $this->stepBody();

        $start = strpos($body, "DB::table('master_rentals')->where('id', \$rental->id)->update(");
        $this->assertNotFalse($start, 'Blok update tidak ditemukan.');

        $updateBlock = substr($body, $start, 500);

        $this->assertStringContainsString("'service_frequency_id' => \$frequencyId", $updateBlock);
        $this->assertStringContainsString("'service_frequency' => \$frequency", $updateBlock);

        // Kolom rental lain — nama, harga, kategori, tipe — tidak boleh ikut tersentuh.
        foreach (['rental_name', 'daily_price', 'monthly_price', 'lost_unit_price', 'category', 'rental_type'] as $untouched) {
            $this->assertStringNotContainsString("'{$untouched}' =>", $updateBlock);
        }
    }

    public function test_a_missing_source_column_skips_the_step_instead_of_failing_the_import(): void
    {
        $body = $this->stepBody();

        $this->assertStringContainsString('INFORMATION_SCHEMA.COLUMNS', $body);
        $this->assertStringContainsString('FrequencyService', $body);
        $this->assertStringContainsString("return ['stats' => \$this->blankStats()];", $body);
    }

    public function test_an_empty_catalog_says_so_once_instead_of_failing_every_row(): void
    {
        $body = $this->stepBody();

        $this->assertStringContainsString('empty-frequency-catalog', $body);
        $this->assertStringContainsString('2026_09_22_000001', $body);
    }

    public function test_a_rental_code_absent_from_catalyst_is_skipped_not_invented(): void
    {
        $body = $this->stepBody();

        $this->assertStringContainsString('rental_code tidak ada di master_rentals', $body);
        $this->assertStringContainsString('FrequencyService kosong di sumber.', $body);
    }
}
