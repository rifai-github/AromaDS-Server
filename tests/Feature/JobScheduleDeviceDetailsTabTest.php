<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobScheduleDeviceDetailsTabTest extends TestCase
{
    public function test_device_details_tab_shows_device_type_name_and_run_suspend_time(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/show.blade.php'));

        $this->assertStringContainsString('<th>Device Type</th>', $view);
        $this->assertStringContainsString('<th>Device Name</th>', $view);
        $this->assertStringContainsString('<th>Run / Suspend Time</th>', $view);
        $this->assertStringContainsString("\$detail->device_type", $view);
        $this->assertStringContainsString("\$detail->device_name", $view);
        $this->assertStringContainsString("\$detail->snapshot['run']", $view);
        $this->assertStringContainsString("\$detail->snapshot['suspend']", $view);
        $this->assertStringContainsString('colspan="11"', $view);
    }

    public function test_job_report_fallback_rows_include_device_name_key(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString("'device_type' => \$report->job_type ?: 'Unknown',", $controller);
        $this->assertStringContainsString("'device_name' => null,", $controller);
    }

    public function test_job_schedule_unit_rows_resolve_room_name_from_job_advice_room(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString('$unitJobAdviceRoomIds = $unitDetailsFromUnits->pluck(\'job_advice_room_id\')', $controller);
        $this->assertStringContainsString('\App\Models\JobAdviceRoom::withTrashed()->whereIn(\'id\', $unitJobAdviceRoomIds)->pluck(\'room_name\', \'id\')', $controller);
        $this->assertStringContainsString('$unit->room_name = $unit->job_advice_room_id', $controller);
    }

    public function test_liquid_level_cell_handles_non_numeric_bucket_codes(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/show.blade.php'));

        $this->assertStringContainsString("\$liquidCodeLabels = ['0' => '0%', '<=10' => '≤10%', '>10' => '>10%', '50' => '50%', '100' => '100%']", $view);
        $this->assertStringContainsString('is_numeric($liquidRaw)', $view);
    }

    public function test_operating_schedule_cell_renders_gear_and_work_pause_minutes(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/show.blade.php'));

        $this->assertStringContainsString("isset(\$session['gear'])", $view);
        $this->assertStringContainsString("isset(\$session['workTimeMinutes'])", $view);
        $this->assertStringContainsString("isset(\$session['pauseTimeMinutes'])", $view);
    }
}
