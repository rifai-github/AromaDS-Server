<?php

namespace Tests\Unit;

use App\Services\Imports\Catalyst\CatalystServiceScheduleBackfillService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Permintaan klien 24 Nov 2026: jadwal service kontrak import Catalyst dibangun dari BA date
 * service pertama (contoh klien: BA 9 Okt, bulanan -> 9 Nov, 9 Des, ...).
 */
class CatalystServiceScheduleBackfillTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cat');
        file_put_contents($path, $content);
        $this->files[] = $path;

        return $path;
    }

    public function test_monthly_schedule_follows_the_client_example_within_the_contract(): void
    {
        $dates = (new CatalystServiceScheduleBackfillService)->scheduleDates(
            Carbon::parse('2026-10-09'), Carbon::parse('2026-10-01'), Carbon::parse('2027-01-31'), 1, 1
        );

        $this->assertSame(['2026-10-09', '2026-11-09', '2026-12-09', '2027-01-09'], $dates);
    }

    public function test_anchor_before_contract_start_skips_dates_outside_the_contract(): void
    {
        $dates = (new CatalystServiceScheduleBackfillService)->scheduleDates(
            Carbon::parse('2026-01-31'), Carbon::parse('2026-03-15'), Carbon::parse('2026-06-30'), 1, 1
        );

        // 31 Jan + n bulan tanpa overflow: 28 Feb (di luar kontrak), 31 Mar, 30 Apr, 31 Mei, 30 Jun.
        $this->assertSame(['2026-03-31', '2026-04-30', '2026-05-31', '2026-06-30'], $dates);
    }

    public function test_quarterly_and_twice_a_month_spacing_match_the_erp_rule(): void
    {
        $service = new CatalystServiceScheduleBackfillService;

        $this->assertSame(
            ['2026-01-10', '2026-04-10', '2026-07-10', '2026-10-10'],
            $service->scheduleDates(Carbon::parse('2026-01-10'), Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'), 3, 1)
        );
        // 2x sebulan: hari kedua = +floor(hari dalam bulan / 2).
        $this->assertSame(
            ['2026-02-01', '2026-02-15', '2026-03-01', '2026-03-16'],
            $service->scheduleDates(Carbon::parse('2026-02-01'), Carbon::parse('2026-02-01'), Carbon::parse('2026-03-20'), 1, 2)
        );
    }

    public function test_anchor_prefers_bas_date_then_csr_period_one_then_inspected_install_then_start_date(): void
    {
        $invoices = $this->csv("\xEF\xBB\xBF\"TransNmbr\",\"ReffType\",\"SONo\",\"PeriodInvoice\",\"ReffDate\",\"BASDate\"\n"
            ."\"INV1\",\"CSR\",\"A-1\",\"3\",\"5/1/2024 12:00:00 AM\",\"4/3/2024 12:00:00 AM\"\n"
            ."\"INV2\",\"CSR\",\"A-1\",\"1\",\"2/1/2024 12:00:00 AM\",\"3/9/2024 12:00:00 AM\"\n"
            ."\"INV3\",\"CSR\",\"B-2\",\"1\",\"6/7/2024 12:00:00 AM\",\"\"\n");
        $contracts = $this->csv("\xEF\xBB\xBF\"TransNmbr\",\"Building\",\"Product\",\"InspectedInstall\"\n"
            ."\"C-3\",\"01\",\"P\",\"8/3/2023 12:00:00 AM\",\n"
            ."\"C-3\",\"01\",\"P\",\"7/1/2023 12:00:00 AM\",\n");

        $service = new CatalystServiceScheduleBackfillService;
        $service->loadAnchorSources($invoices, $contracts);

        $this->assertSame(['2024-03-09', 'bas_date'], $service->anchorFor((object) ['contract_number' => 'A-1', 'start_date' => '2024-01-01']));
        $this->assertSame(['2024-06-07', 'csr_period_1'], $service->anchorFor((object) ['contract_number' => 'B-2', 'start_date' => '2024-01-01']));
        $this->assertSame(['2023-07-01', 'inspected_install'], $service->anchorFor((object) ['contract_number' => 'C-3', 'start_date' => '2023-01-01']));
        $this->assertSame(['2025-05-01', 'start_date'], $service->anchorFor((object) ['contract_number' => 'D-4', 'start_date' => '2025-05-01 00:00:00']));
    }
}
