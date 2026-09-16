<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Repair periode service harus berjalan untuk SEMUA baris service pertama.
 *
 * Satu Job Advice punya satu baris service pertama per ruangan yang dipasang.
 * `generateAllRemainingServices()` hanya memakai ruangan milik baris yang
 * diberikan padanya (lewat `getFinalizedRoomIdsForSchedule`), jadi memanggilnya
 * dengan satu baris saja hanya menghasilkan periode lanjutan untuk ruangan itu
 * dan diam-diam melewatkan ruangan saudaranya.
 *
 * Terbukti di produksi 16 Sep 2026: ADS-JA/26-09/0003 punya 3 ruangan (Office
 * Hall, Meeting, Server) dengan 3 baris service pertama, tetapi repair hanya
 * membuatkan periode 2-4 untuk Ruang Office Hall.
 *
 * Aman dipanggil berulang: pengecekan "periode sudah ada" di dalam generator
 * di-scope per job_advice_room, jadi baris yang sudah punya lanjutan dilewati.
 *
 * Pola cakupan-saudara ini sudah berulang di repo ini (verifyJob mobile,
 * completeRoom), jadi dikunci test.
 */
class RepairFrequencyPeriodsSiblingScopeTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents(app_path('Console/Commands/RepairWrongFrequencyServicePeriods.php'));
    }

    public function test_it_collects_every_first_service_row_not_just_one(): void
    {
        $source = $this->source();

        $this->assertStringNotContainsString(
            "\$services->firstWhere('period', 1)",
            $source,
            'Mengambil satu baris service pertama hanya menghasilkan lanjutan untuk ruangan baris itu.'
        );
        $this->assertStringContainsString('$firstServices', $source);
    }

    public function test_it_runs_the_generator_once_per_first_service_row(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            '/foreach \(\$firstServices as \$firstService\) \{\s*\$created = \$method->invoke\(/',
            $source,
            'Generator harus dipanggil sekali untuk tiap baris service pertama.'
        );
    }
}
