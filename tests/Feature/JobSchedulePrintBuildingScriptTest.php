<?php

namespace Tests\Feature;

use Tests\TestCase;

class JobSchedulePrintBuildingScriptTest extends TestCase
{
    public function test_print_csr_loads_building_location_relations(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/Operational/JobScheduleController.php'));

        $this->assertStringContainsString("'building.city'", $controller);
        $this->assertStringContainsString("'building.province'", $controller);
        $this->assertStringContainsString("'building.district'", $controller);
        $this->assertStringContainsString("'building.subdistrict'", $controller);
    }

    public function test_print_csr_header_contains_building_for_all_job_report_types(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/pdf-csr.blade.php'));

        $this->assertStringContainsString('$buildingName = $building?->nama_gedung', $view);
        $this->assertStringContainsString('$buildingAddressParts = collect', $view);
        $this->assertStringContainsString('<td class="label">Building:</td>', $view);
        $this->assertStringContainsString('<td>{{ $buildingName }}</td>', $view);
        $this->assertStringContainsString('<td class="label">Address:</td>', $view);
        $this->assertStringContainsString('str_contains($jobTypeLower, \'remove\')', $view);
        $this->assertStringContainsString('strtoupper($mainJob->type) . \' REPORT\'', $view);
    }
}
