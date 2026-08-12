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

        $this->assertStringContainsString('$buildingName = $building?->building_name', $view);
        $this->assertStringContainsString('$buildingAddressParts = collect', $view);
        $this->assertStringContainsString('<div class="section-label">Building :</div>', $view);
        $this->assertStringContainsString('<div class="building-title">{{ $buildingName }}</div>', $view);
        $this->assertStringContainsString('str_contains($jobTypeLower, \'remove\')', $view);
        $this->assertStringContainsString('strtoupper($mainJob->type) . \' REPORT\'', $view);
    }

    public function test_print_csr_uses_invoice_style_layout(): void
    {
        $view = file_get_contents(resource_path('views/operational/job-schedules/pdf-csr.blade.php'));

        $this->assertStringContainsString('class="letterhead-table"', $view);
        $this->assertStringContainsString('class="document-title"', $view);
        $this->assertStringContainsString('class="meta-table"', $view);
        $this->assertStringContainsString('<th style="width: 18%;">Reference</th>', $view);
        $this->assertStringContainsString('<th style="width: 27%;">Item</th>', $view);
        $this->assertStringContainsString('<th style="width: 31%;">Room</th>', $view);
        $this->assertStringContainsString('This Job Report was generated on', $view);
        $this->assertStringContainsString('An ISO 14001:2015 Certified Company | IAS Accredited', $view);
    }
}
