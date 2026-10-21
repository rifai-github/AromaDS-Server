<?php

namespace Tests\Feature;

use App\Models\JobReport;
use App\Services\Operational\JobWebCompletionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * QA 21 Oct 2026 (MDO-CSR/26-10/0007): units saved from the web "Set Unit & Jadwal
 * Aroma" modal stored {schedule:{start,end,intensity}}, a shape Device Details can't
 * read, and the web BA never copied the scanned MAC into the JobReport - so the BA
 * record showed up as a second "service_first / NO MAC" row. These lock the web onto
 * the APK's device_snapshot shape and verifyJob's report sync.
 */
class JobWebDeviceSnapshotParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('job_schedule_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->foreignId('job_advice_room_id')->nullable();
            $table->string('mac')->nullable();
            $table->json('device_snapshot')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('job_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_schedule_id')->nullable();
            $table->string('job_type')->nullable();
            $table->string('unit_serial_number')->nullable();
            $table->string('unit_mac_address')->nullable();
            $table->timestamp('qr_scan_at')->nullable();
            $table->json('device_snapshot')->nullable();
            $table->string('device_online_status')->nullable();
            $table->integer('device_liquid_level')->nullable();
            $table->integer('device_fan_level')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('job_reports');
        Schema::dropIfExists('job_schedule_units');

        parent::tearDown();
    }

    public function test_apk_shaped_snapshot_passes_through_unchanged(): void
    {
        $apk = [
            'liquidLevel' => '<=10',
            'run' => 30,
            'suspend' => 120,
            'fanLevel' => 3,
            'status' => 1,
            'schedule' => [[
                'days' => [1, 3, 5],
                'startTime' => '08:00',
                'endTime' => '18:00',
                'workTimeMinutes' => 60,
                'pauseTimeMinutes' => 30,
                'gear' => 50,
            ]],
        ];

        $this->assertSame($apk, JobWebCompletionService::normalizeDeviceSnapshot($apk));
    }

    public function test_legacy_web_modal_schedule_is_mapped_onto_the_apk_keys(): void
    {
        $snapshot = JobWebCompletionService::normalizeDeviceSnapshot([
            'schedule' => ['start' => '08:00', 'end' => '17:00', 'intensity' => 'medium'],
        ]);

        $this->assertSame([
            'schedule' => [['days' => [], 'startTime' => '08:00', 'endTime' => '17:00']],
        ], $snapshot);
    }

    public function test_form_strings_are_coerced_and_junk_dropped(): void
    {
        $snapshot = JobWebCompletionService::normalizeDeviceSnapshot([
            'liquidLevel' => '75',          // not an APK bucket
            'run' => '15',
            'fanLevel' => '9',              // clamped to 5
            'status' => 'false',
            'evil' => '<script>',
            'schedule' => [[
                'days' => ['7', '2', '2', '9', 'x'],
                'startTime' => '7:05',
                'endTime' => '25:00',       // invalid clock
                'gear' => '0',              // clamped to 1
            ]],
        ]);

        $this->assertSame([
            'run' => 15,
            'fanLevel' => 5,
            'status' => 0,
            'schedule' => [['days' => [2, 7], 'startTime' => '07:05', 'endTime' => null, 'gear' => 1]],
        ], $snapshot);
    }

    public function test_web_ba_report_takes_the_scanned_unit_mac_and_snapshot(): void
    {
        DB::table('job_schedule_units')->insert([
            'job_schedule_id' => 353,
            'job_advice_room_id' => 75863,
            'mac' => 'DISW300261019005',
            'device_snapshot' => json_encode(['liquidLevel' => '50', 'fanLevel' => 2, 'schedule' => [['days' => [1], 'startTime' => '08:00', 'endTime' => '17:00']]]),
            'scanned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = JobReport::create(['job_schedule_id' => 353, 'job_type' => 'service_first']);

        app(JobWebCompletionService::class)->syncJobReportFromScannedUnit($report);

        $report->refresh();
        $this->assertSame('DISW300261019005', $report->unit_mac_address);
        $this->assertSame('DISW300261019005', $report->unit_serial_number);
        $this->assertSame(1, (int) $report->device_liquid_level, "'50' bucket maps to device code 1, like mobile");
        $this->assertSame(2, (int) $report->device_fan_level);
        $this->assertSame('50', $report->device_snapshot['liquidLevel'], 'Stored as JSON object, not double-encoded');
        $this->assertNotNull($report->qr_scan_at);
    }

    public function test_web_ba_report_without_scan_is_left_alone(): void
    {
        $report = JobReport::create(['job_schedule_id' => 999, 'job_type' => 'install']);

        app(JobWebCompletionService::class)->syncJobReportFromScannedUnit($report);

        $this->assertNull($report->refresh()->unit_mac_address);
    }
}
