<?php

namespace Tests\Unit;

use App\Reports\BusinessReport;
use App\Reports\ReportData;
use App\Reports\ReportFilters;
use App\Reports\ReportRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Spec Halaman Report 24 Nov 2026: filter range tanggal wajib di semua laporan dan kode
 * "Status Ops" job (RMC = cancelled, SUS/DPF dari status, sisanya dari nomor job).
 */
class BusinessReportSupportTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function report(array $range): BusinessReport
    {
        return new class($range) extends BusinessReport
        {
            public function __construct(private array $range) {}

            public function key(): string { return 'uji'; }

            public function title(): string { return 'Uji'; }

            public function description(): string { return ''; }

            public function dateLabel(): string { return 'Tanggal'; }

            public function columns(): array { return []; }

            public function rows(ReportFilters $filters): Collection { return collect(); }

            public function defaultRange(): array { return $this->range; }

            public function extraFilters(): array
            {
                return ['invoice' => ['label' => 'Invoice', 'options' => ['ada' => 'Ada']]];
            }
        };
    }

    public function test_filters_default_to_the_report_range_and_ignore_bad_input(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');
        $report = $this->report([now()->subDays(30)->startOfDay(), now()->startOfDay()]);

        $filters = ReportFilters::fromRequest(Request::create('/x', 'GET', ['date_from' => 'kemarin', 'branch_id' => 'abc']), $report);

        $this->assertSame('2026-10-25', $filters->dateFrom->toDateString());
        $this->assertSame('2026-11-24 23:59:59', $filters->dateTo->toDateTimeString());
        $this->assertNull($filters->branchId);
        $this->assertNull($filters->extra('invoice'));
    }

    public function test_filters_swap_a_reversed_range_and_keep_extra_values_in_the_export_query(): void
    {
        $report = $this->report([now()->startOfDay(), now()->startOfDay()]);

        $filters = ReportFilters::fromRequest(Request::create('/x', 'GET', [
            'date_from' => '2026-12-31', 'date_to' => '2026-12-01', 'branch_id' => '2', 'search' => '  JKT ', 'invoice' => 'ada',
        ]), $report);

        $this->assertSame('2026-12-01', $filters->dateFrom->toDateString());
        $this->assertSame('2026-12-31', $filters->dateTo->toDateString());
        $this->assertSame([
            'date_from' => '2026-12-01', 'date_to' => '2026-12-31', 'branch_id' => '2', 'search' => 'JKT', 'invoice' => 'ada',
        ], $filters->toQuery());
    }

    public function test_status_ops_code_comes_from_status_first_then_job_number(): void
    {
        $data = new ReportData;

        $this->assertSame('CSR', $data->jobStatusCode('done_job', 'SBY-CSR/26-09/0028'));
        $this->assertSame('IR', $data->jobStatusCode('new_job', 'JKT-IR/26-06/0002'));
        $this->assertSame('SUS', $data->jobStatusCode('suspend', 'JKT-CSR/26-06/0002'));
        $this->assertSame('DPF', $data->jobStatusCode('dpf', 'JKT-CSR/26-06/0002'));
        $this->assertSame('RMC', $data->jobStatusCode('cancelled', 'JKT-RV/26-06/0001'));
        $this->assertNull($data->jobStatusCode('new_job', null));
    }

    public function test_registry_has_the_six_reports_with_unique_keys(): void
    {
        $keys = array_map(fn (BusinessReport $report) => $report->key(), ReportRegistry::all());

        $this->assertSame(['contract-list', 'suspend', 'lock-service', 'agreement-sign', 'rental-service', 'generate-invoice'], $keys);
        $this->assertSame('reports.lock-service', ReportRegistry::find('lock-service')->permission());
        $this->assertNull(ReportRegistry::find('tidak-ada'));
    }
}
