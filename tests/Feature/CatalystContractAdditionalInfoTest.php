<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tab "Additional Info" di detail kontrak kosong untuk SELURUH kontrak hasil import
 * (QA "Revisi 1", 21 Sep 2026 — dicek di produksi: 10.951 kontrak, nol yang punya
 * ppn_code / customer_signing_1_id / external_remark).
 *
 * Step `contracts()` memang tidak memetakan satu pun kolomnya, padahal step `quotations`
 * sudah memetakan Remark/RemarkInternal miliknya sendiri.
 *
 * Isi sumbernya diperiksa langsung ke MKTContractHd (71 kolom, 10.984 baris, 22 Sep 2026):
 *
 *   ADA          EmpSign 10.984 terisi, ContractNote 758, InternalMemo 2.467
 *   KOSONG       ContactName1/2/3 — kolomnya ada, NOL baris terisi
 *   TIDAK ADA    TTD Customer 4, Tanggal Install, Tanggal Service Pertama, PIC Service Email
 *   BUKAN MILIK KONTRAK   Kode PPN — adanya di MsCustomer.KodePPn
 *
 * Jadi yang bisa diimport hanya tiga. Test ini mengunci ketiganya sekaligus mencegah
 * "melengkapi" sisanya dari tabel yang memang tidak menyimpannya.
 */
class CatalystContractAdditionalInfoTest extends TestCase
{
    private function importerSource(): string
    {
        return file_get_contents(
            app_path('Services/Imports/Catalyst/CatalystMasterDataImporter.php')
        );
    }

    /**
     * @return string the body of the contracts() step
     */
    private function contractsStep(): string
    {
        $source = $this->importerSource();

        $start = strpos($source, 'protected function contracts(): array');
        $this->assertNotFalse($start, 'Step contracts() tidak ditemukan di importer.');

        $end = strpos($source, 'protected function quotation_existing_contracts', $start);
        $this->assertNotFalse($end, 'Batas akhir step contracts() tidak ditemukan.');

        return substr($source, $start, $end - $start);
    }

    public function test_contract_step_maps_the_three_fields_the_source_actually_carries(): void
    {
        $step = $this->contractsStep();

        $this->assertStringContainsString(
            "'internal_signing_id' => \$this->findMappedTargetId('MsEmployee', \$this->makeKey(\$row['EmpSign'] ?? null), 'users')",
            $step
        );
        $this->assertStringContainsString(
            "'external_remark' => \$this->cleanString(\$row['ContractNote'] ?? null)",
            $step
        );
        $this->assertStringContainsString(
            "'internal_remark' => \$this->cleanString(\$row['InternalMemo'] ?? null)",
            $step
        );
    }

    public function test_contract_step_does_not_invent_the_fields_the_source_lacks(): void
    {
        $step = $this->contractsStep();

        // ContactName1/2/3 ada kolomnya tapi nol baris terisi; memetakannya cuma
        // menambah kerja tanpa hasil, dan menyamarkan bahwa datanya memang tidak ada.
        foreach (['ContactName1', 'ContactName2', 'ContactName3'] as $emptyColumn) {
            $this->assertStringNotContainsString("\$row['{$emptyColumn}']", $step);
        }

        // Kolom-kolom ini tidak ada sama sekali di MKTContractHd. Kalau suatu hari ada
        // yang menambahkannya, itu harus lewat sumber lain — bukan dari tabel ini.
        foreach (['install_date', 'first_service_date', 'pic_service_email', 'customer_signing_'] as $absent) {
            $this->assertStringNotContainsString("'{$absent}", $step);
        }
    }

    public function test_the_reason_each_field_was_left_out_stays_written_down(): void
    {
        $step = $this->contractsStep();

        $this->assertStringContainsString('MKTContractHd', $step);
        $this->assertStringContainsString('MsCustomer', $step);
        $this->assertStringContainsString('ContactName1/2/3', $step);
    }
}
